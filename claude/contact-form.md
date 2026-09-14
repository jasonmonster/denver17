# Contact Form — Build & Decision Record

Denver Elks Lodge #17 · `denver17` theme · built Aug 2026, live on `denverelks.org`

A contact form with no plugin dependency. Emails the lodge secretary, stores every
submission as a fallback, and filters spam locally with no third-party service.

## Files

```
inc/contact-form.php                      CPT, submission handler, mail, admin UI, spam scoring
template-parts/blocks/contact-form.php    front-end markup
blocks/contact-form/block.json            block definition
blocks/contact-form/index.js              editor script (vanilla wp.*, no build step)
assets/css/contact-form.css               enqueued with filemtime() versioning
```

`functions.php` requires `inc/contact-form.php`. A `[denver17_contact_form]`
shortcode exists as an alternative to the block.

## Why the theme and not a plugin

This breaks the project's plugin-owns-data-layer rule, deliberately. `denver17-events`
is an events plugin and contact messages aren't events. A third repo would have cost a
dedicated deploy key, its own GitHub secrets, and a one-time manual server clone for
~700 lines of code. The theme is bespoke to this lodge and will never be swapped, so
the usual "data must survive a theme change" argument doesn't apply.

If contact ever grows into something bigger (routing, assignment, canned replies), move
it to its own plugin then. The CPT and meta keys port over unchanged.

## Storage before delivery

The handler writes an `elks_contact_msg` post *first*, then calls `wp_mail()`, then
records the result in `_contact_mail_sent`. Order matters: a mail outage on this host
would otherwise lose messages silently, and mail deliverability is already flagged as
the highest-risk dependency across this project.

A failed send leaves the message intact in wp-admin, shows "Failed" in the list column
with the PHPMailer error on hover, and throws a red admin notice on the Dashboard and
the Contact Messages screen if anything failed in the last 30 days. Reply-To is the
sender, so replying from the inbox goes to the right person.

Meta keys: `_contact_name`, `_contact_email`, `_contact_phone`, `_contact_topic`,
`_contact_ip`, `_contact_ua`, `_contact_mail_sent`, `_contact_mail_error`,
`_contact_spam_score`, `_contact_spam_reasons`.

## No nonce, on purpose

The form is unauthenticated and takes no action on behalf of a logged-in user, so CSRF
buys an attacker nothing. A nonce on a page served from SpinupWP's FastCGI cache goes
stale and fails real submissions, which is a genuine cost against no benefit.

Forgery protection comes from a hash-signed timestamp instead (`wp_hash($ts . '|d17contact')`),
which also drives the timing checks. The admin-side "Not spam — deliver it" button does
use a nonce; that's a logged-in action on an uncached screen.

## Spam: five layers, no third-party service

No captcha, no Akismet, no API key that stops working in two years when nobody
remembers it existed.

1. **Honeypot** — `d17_website`, positioned off-screen rather than `display:none`,
   which some bots skip. Filled in means the submission is silently discarded and the
   sender still sees the success screen.
2. **Timing** — submitted under 3 seconds is automated; older than a week means a stale
   cached page. Both fail with their own distinct message.
3. **Rate limit** — 5 per IP per hour, transient-backed. Note: if Cloudflare ever goes
   in front of this site, add `HTTP_CF_CONNECTING_IP` to `denver17_contact_ip()` or the
   limit will see one shared address for everyone.
4. **No links, at all** — any URL in the name, phone, or message fails validation with
   a message telling the sender to remove it. The lodge has no legitimate need to
   receive a URL. Detection covers schemes, bare `www.`, BBCode/markdown, and bare
   domains like `cutt.ly` across ~40 TLDs. Email addresses and lodge domains are
   stripped before checking, so `bob@gmail.com`, `denverelks.org/events`,
   `303.455.3557`, and `$50.00` all pass clean.
5. **Content scoring** — anything at or above 3 is filed under a custom `elks_spam`
   post status: stored, never emailed, out of the main list behind a "Spam" filter link.
   The sender sees the normal success screen so a bot learns nothing.

Scoring signals: bot name shape (single token with an internal capital, like
"RobertBup" — surname prefixes Mc/Mac/De/Van/O' are exempt), digits in the name, a phone
that isn't North American (11 digits starting with 8 is the Russian format these bots
default to), sender domains on `.ru`/`.su`/`.tk`/`.xyz` and friends, rare diacritics
(Icelandic/Nordic/Turkish — Spanish, German and French accents are deliberately absent,
since Denver has speakers of all three), sales-pitch shape (second-person targeting +
seller voice + offer language, any two scores 4), SEO/pharma/crypto vocabulary, no MX or
A record on the email domain, the same body twice in 24 hours, and long text with almost
no whitespace.

Two rules key off whether the message mentions the lodge at all, matched against a
vocabulary list (`elk`, `lodge`, `rent`, `hall`, `beer`, `membership`, `Elkstock`,
weekdays, and ~40 more). A short message with no lodge reference scores 2 — enough to
combine, never enough alone, so "Are you open today?" still gets through. On top of
that, a price enquiry that never says what's being priced scores 3, and mailing-list
harvesting language ("subscribe to your", "send me news and updates") scores 2.

Two rules run outside the score, as outright blocks. The same message body arriving
from a *different* address than one already on file is spam regardless of what it says —
which is what catches a template drip-fed over weeks under rotating names. Matching on a
different sender means a member resending their own unanswered message isn't punished
for it. Fingerprints normalise case and punctuation, so cosmetic edits don't evade it.

**The filter trains itself.** Any address that already has a message sitting in the Spam
view is blocked outright on its next attempt. Clicking "Not spam — deliver it" publishes
that message, which stops it matching and unblocks the sender in the same action — so
the only maintenance is the one Leo or Megan would do anyway.

**False positives are one click.** The edit screen shows the score and exactly which
rules fired, with a "Not spam — deliver it" button that publishes the message and sends
the notification that was withheld. Held spam self-deletes after 30 days.

### What got through, and what changed

**Round one — the Vantovo pitch.** An AI-traffic sales pitch from a real Gmail address
with a plausible name and one shortened link. Scored 0: the link rule needed two, the
domain had valid MX, no terms matched. Response was the no-links rule, the pitch
detector, a threshold drop from 4 to 3, and a much longer term list. It scores 9 now,
and is link-rejected before scoring runs.

**Round two — four form probes.** Short, polite, no links, testing whether the form
delivers: two identical "I wanted to know your price" messages (Icelandic and Spanish)
from "RobertBup" with 11-digit Russian phone numbers, a mail.ru address asking to
subscribe to the mailing list, and a Gmail address asking for "updates about weekly
updates". Round-one rules caught none of them. Round two added name shape, phone shape,
domain reputation, diacritics, the lodge-reference test, and the two probe templates.
They now score 16, 13, 8 and 4.

**Round three — the site's own name as camouflage.** "I would like more information.
Please contact me by email — denver elks lodge #17." The trailing mail-merged site title
satisfied the round-two lodge-reference test, which was the whole point of it. Fix: strip
the site's own name and domain from the message before testing whether the sender
demonstrates any knowledge of the lodge. Added alongside it: a contentless
information-request probe ("more information", "please contact me", "interested in your"),
gated on the lodge test so "I'd like more information about renting the hall" is
unaffected; a weak signal for an address bearing no relation to the stated name with no
separator in the local part (`Matthew Anderson <xEisei@gmail.com>`); and the persistent
duplicate-body rule above, since these arrive verbatim over weeks and the old 24-hour
transient never saw the repeat. Scores 7.

Regression-tested against twelve legitimate messages: a rental enquiry, a membership
question, an Elkstock guest question, a hours-are-wrong bug report, "What time does the
beer garden open Friday?", a real request to join the email list, "Are you open today?",
a sender named McDonald, a one-word sender name, a rental enquiry written in Spanish, a
genuine "more information about renting the hall" request, and a vendor following up from
a company address. All score 0 except the bug report and the vendor at 2.

The suite lives outside the repo, in the session workspace. Worth keeping: every round of
tightening has been a question of whether the new rule breaks an old legitimate case, and
guessing at that is how a contact form quietly stops delivering.

### Tuning without editing code

| Filter | Does |
|---|---|
| `denver17_contact_recipient` | Route by topic slug |
| `denver17_contact_topics` | Change the topic dropdown |
| `denver17_contact_spam_terms` | Add/remove scored vocabulary |
| `denver17_contact_spam_domains` | Sender domains/TLDs scored as suspect |
| `denver17_contact_lodge_words` | Vocabulary that marks a message as lodge-relevant |
| `denver17_contact_site_identity` | Site-name strings stripped before the lodge test |
| `denver17_contact_spam_score` | Final say on any score |
| `denver17_contact_allowed_domains` | Domains exempt from the no-links rule |
| `denver17_contact_blocklist` | Email/IP substrings blocked outright |
| `denver17_contact_mail` | Recipient, subject, body, headers |
| `denver17_contact_retention_days` | Prune real messages (default 0 = keep forever) |
| `denver17_contact_spam_retention_days` | Prune held spam (default 30) |

## Admin

Contact Messages sits at menu position 26. "Add New" is disabled — messages only arrive
through the form. Columns: From, Email, Phone, Message excerpt, Emailed, Received. The
sender-details meta box shows everything captured plus delivery status.

Recipient is set at **Appearance → Customize → Contact → Contact form recipient**. It
accepts one address or several separated by commas (`leo@…, megan@…`); everyone listed
goes in the To line and sees each other, which is fine for lodge officers. Invalid
entries are dropped silently rather than failing the whole field, and duplicates are
collapsed. Blank falls back to the WordPress admin address, which is worth checking
after any handoff — it's the quiet failure mode where messages keep arriving to the
wrong person.

For different addresses per topic rather than everyone getting everything, hook
`denver17_contact_recipient`, which receives the topic slug.

## Redelivering

Both PHP files were built to lint clean (`php -l`), the editor script against
`node --check`. `install-contact-form.sh` in the session workspace writes all files into
the repo and adds the `functions.php` require line idempotently; re-running it is the
update path.

## Not built, on purpose

No auto-reply to the sender (doubles mail volume against the site's weakest dependency,
and every bounce is a deliverability hit). No file uploads. No CSV export — the list
screen and search cover the actual need at this volume. No multi-recipient routing until
someone asks for it; the filter is there when they do.
