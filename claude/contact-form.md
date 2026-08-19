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

Scoring signals: sales-pitch shape (second-person targeting + seller voice + offer
language — any two scores 4), SEO/pharma/crypto vocabulary, non-Latin scripts
(Cyrillic/CJK/Arabic/Thai; accented Latin passes), email domain with no MX or A record,
the same body twice in 24 hours, and long text with almost no whitespace.

**False positives are one click.** The edit screen shows the score and exactly which
rules fired, with a "Not spam — deliver it" button that publishes the message and sends
the notification that was withheld. Held spam self-deletes after 30 days.

### What got through before this, and why

The first live spam was a Vantovo AI-traffic pitch from a real Gmail address with a
plausible name and one shortened link. It scored 0 against the original rules: the link
count needed two, the domain had valid MX, and none of the original terms matched.

Rewritten, it scores 9 and is link-rejected before scoring even runs. Strip the URL and
it still scores 9. Tested alongside a rental enquiry, a membership question, an Elkstock
question, and a hours-are-wrong bug report — all score 0, except the bug report at 2,
which is under the line but only just. If a member's genuine complaint ever lands in
Spam, that's the rule to loosen: require two pitch signals instead of one.

### Tuning without editing code

| Filter | Does |
|---|---|
| `denver17_contact_recipient` | Route by topic slug |
| `denver17_contact_topics` | Change the topic dropdown |
| `denver17_contact_spam_terms` | Add/remove scored vocabulary |
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
