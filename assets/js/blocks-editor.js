/**
 * Denver Elks Lodge #17 — Block Editor UI
 *
 * Registers all denver17/* blocks client-side using wp.* globals.
 * No build step — uses createElement directly (no JSX).
 *
 * Edit components use the Inspector sidebar pattern: all fields live in
 * the right-hand panel, canvas shows a minimal labeled placeholder.
 * These are one-off layout blocks used once each on the homepage, so
 * a live preview in the canvas isn't necessary.
 */

( function () {
  var el             = wp.element.createElement;
  var registerBlock  = wp.blocks.registerBlockType;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var useBlockProps  = wp.blockEditor.useBlockProps;
  var MediaUpload    = wp.blockEditor.MediaUpload;
  var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
  var PanelBody      = wp.components.PanelBody;
  var TextControl    = wp.components.TextControl;
  var TextareaControl = wp.components.TextareaControl;
  var SelectControl  = wp.components.SelectControl;
  var Button         = wp.components.Button;
  var Fragment       = wp.element.Fragment;

  // ---------------------------------------------------------------------------
  // Shared helpers
  // ---------------------------------------------------------------------------

  /**
   * Renders an image picker (media library button + preview + remove).
   * @param {object} imageAttr  — current image attribute {url, id, alt}
   * @param {string} label      — label shown above the picker
   * @param {function} onChange — called with new {url, id, alt} or {}
   */
  function imagePicker( imageAttr, label, onChange ) {
    var hasImage = imageAttr && imageAttr.url;
    return el( 'div', { style: { marginBottom: '16px' } },
      el( 'p', { style: { marginBottom: '8px', fontWeight: 600, fontSize: '11px', textTransform: 'uppercase', color: '#1e1e1e' } }, label ),
      hasImage
        ? el( 'img', {
            src: imageAttr.url,
            alt: imageAttr.alt || '',
            style: { width: '100%', height: 'auto', marginBottom: '8px', borderRadius: '4px' }
          } )
        : null,
      el( MediaUploadCheck, null,
        el( MediaUpload, {
          onSelect: function ( media ) {
            onChange( { url: media.url, id: media.id, alt: media.alt || '' } );
          },
          allowedTypes: [ 'image' ],
          value: hasImage ? imageAttr.id : null,
          render: function ( ref ) {
            return el( Fragment, null,
              el( Button, { onClick: ref.open, variant: 'secondary', style: { marginRight: '8px' } },
                hasImage ? 'Change image' : 'Select image'
              ),
              hasImage
                ? el( Button, {
                    onClick: function () { onChange( {} ); },
                    variant: 'link',
                    isDestructive: true
                  }, 'Remove' )
                : null
            );
          }
        } )
      )
    );
  }

  /**
   * Canvas placeholder shown in the block editor.
   * @param {string} blockLabel   — block name
   * @param {string} previewText  — short preview of key content
   */
  function canvasPlaceholder( blockLabel, previewText ) {
    return el( 'div', {
      style: {
        background: '#1d1a47',
        color: '#EEEDFE',
        padding: '24px',
        borderRadius: '8px',
        fontFamily: 'sans-serif',
      }
    },
      el( 'div', {
        style: { fontSize: '10px', letterSpacing: '2px', textTransform: 'uppercase', color: '#7F77DD', marginBottom: '8px' }
      }, 'Denver Elks #17 — ' + blockLabel ),
      el( 'div', { style: { fontSize: '16px', fontWeight: 600 } }, previewText || '(no content yet)' )
    );
  }

  // ---------------------------------------------------------------------------
  // denver17/hero
  // ---------------------------------------------------------------------------

  registerBlock( 'denver17/hero', {
    edit: function ( props ) {
      var attrs = props.attributes;
      var set   = props.setAttributes;
      var blockProps = useBlockProps();

      return el( Fragment, null,
        el( InspectorControls, null,
          el( PanelBody, { title: 'Background Image', initialOpen: true },
            imagePicker( attrs.backgroundImage, 'Background image', function ( img ) {
              set( { backgroundImage: img } );
            } )
          ),
          el( PanelBody, { title: 'Headline', initialOpen: true },
            el( TextControl, {
              label: 'Eyebrow',
              value: attrs.eyebrow,
              onChange: function ( v ) { set( { eyebrow: v } ); }
            } ),
            el( TextControl, {
              label: 'Heading — Line 1',
              value: attrs.headingLine1,
              onChange: function ( v ) { set( { headingLine1: v } ); }
            } ),
            el( TextControl, {
              label: 'Heading — Line 2 (accent color)',
              value: attrs.headingLine2,
              onChange: function ( v ) { set( { headingLine2: v } ); }
            } ),
            el( TextareaControl, {
              label: 'Subtext',
              value: attrs.subtext,
              onChange: function ( v ) { set( { subtext: v } ); }
            } )
          ),
          el( PanelBody, { title: 'CTA Button', initialOpen: false },
            el( TextControl, {
              label: 'Button text',
              value: attrs.ctaText,
              onChange: function ( v ) { set( { ctaText: v } ); }
            } ),
            el( TextControl, {
              label: 'Button URL',
              value: attrs.ctaUrl,
              type: 'url',
              onChange: function ( v ) { set( { ctaUrl: v } ); }
            } )
          )
        ),
        el( 'div', blockProps,
          canvasPlaceholder( 'Hero',
            ( attrs.headingLine1 || '' ) + ' / ' + ( attrs.headingLine2 || '' )
          )
        )
      );
    },
    save: function () { return null; }
  } );

  // ---------------------------------------------------------------------------
  // denver17/feature-split
  // ---------------------------------------------------------------------------

  registerBlock( 'denver17/feature-split', {
    edit: function ( props ) {
      var attrs = props.attributes;
      var set   = props.setAttributes;
      var blockProps = useBlockProps();

      return el( Fragment, null,
        el( InspectorControls, null,
          el( PanelBody, { title: 'Layout & Style', initialOpen: true },
            el( SelectControl, {
              label: 'Layout',
              value: attrs.layout,
              options: [
                { label: 'Image left, text right', value: 'image-left' },
                { label: 'Text left, image right', value: 'text-left' }
              ],
              onChange: function ( v ) { set( { layout: v } ); }
            } ),
            el( SelectControl, {
              label: 'Background variant',
              value: attrs.variant,
              options: [
                { label: 'Dark (deep purple)', value: 'dark' },
                { label: 'Mid (medium purple)', value: 'mid' }
              ],
              onChange: function ( v ) { set( { variant: v } ); }
            } )
          ),
          el( PanelBody, { title: 'Image', initialOpen: true },
            imagePicker( attrs.image, 'Section image', function ( img ) {
              set( { image: img } );
            } )
          ),
          el( PanelBody, { title: 'Content', initialOpen: true },
            el( TextControl, {
              label: 'Eyebrow tag',
              value: attrs.tag,
              onChange: function ( v ) { set( { tag: v } ); }
            } ),
            el( TextareaControl, {
              label: 'Heading (one phrase per line)',
              value: attrs.heading,
              onChange: function ( v ) { set( { heading: v } ); }
            } ),
            el( TextareaControl, {
              label: 'Body copy',
              value: attrs.body,
              onChange: function ( v ) { set( { body: v } ); }
            } ),
            el( TextControl, {
              label: 'Link text',
              value: attrs.linkText,
              onChange: function ( v ) { set( { linkText: v } ); }
            } ),
            el( TextControl, {
              label: 'Link URL',
              value: attrs.linkUrl,
              type: 'url',
              onChange: function ( v ) { set( { linkUrl: v } ); }
            } )
          )
        ),
        el( 'div', blockProps,
          canvasPlaceholder( 'Feature Split — ' + ( attrs.variant === 'mid' ? 'Mid' : 'Dark' ),
            attrs.tag || attrs.heading || '(no content yet)'
          )
        )
      );
    },
    save: function () { return null; }
  } );

  // ---------------------------------------------------------------------------
  // denver17/membership-steps
  // ---------------------------------------------------------------------------

  registerBlock( 'denver17/membership-steps', {
    edit: function ( props ) {
      var attrs = props.attributes;
      var set   = props.setAttributes;
      var blockProps = useBlockProps();

      function stepPanel( num, titleKey, bodyKey, imageKey ) {
        return el( PanelBody, { title: 'Step ' + num, initialOpen: num === 1 },
          imagePicker( attrs[ imageKey ], 'Photo', function ( img ) {
            var update = {};
            update[ imageKey ] = img;
            set( update );
          } ),
          el( TextControl, {
            label: 'Title',
            value: attrs[ titleKey ],
            onChange: function ( v ) {
              var update = {};
              update[ titleKey ] = v;
              set( update );
            }
          } ),
          el( TextareaControl, {
            label: 'Body',
            value: attrs[ bodyKey ],
            onChange: function ( v ) {
              var update = {};
              update[ bodyKey ] = v;
              set( update );
            }
          } )
        );
      }

      return el( Fragment, null,
        el( InspectorControls, null,
          el( PanelBody, { title: 'Section Header', initialOpen: false },
            el( TextControl, {
              label: 'Tag',
              value: attrs.sectionTag,
              onChange: function ( v ) { set( { sectionTag: v } ); }
            } ),
            el( TextControl, {
              label: 'Heading',
              value: attrs.sectionHeading,
              onChange: function ( v ) { set( { sectionHeading: v } ); }
            } )
          ),
          stepPanel( 1, 'step1Title', 'step1Body', 'step1Image' ),
          stepPanel( 2, 'step2Title', 'step2Body', 'step2Image' ),
          stepPanel( 3, 'step3Title', 'step3Body', 'step3Image' )
        ),
        el( 'div', blockProps,
          canvasPlaceholder( 'Membership Steps', attrs.sectionHeading )
        )
      );
    },
    save: function () { return null; }
  } );

  // ---------------------------------------------------------------------------
  // denver17/events-band
  // ---------------------------------------------------------------------------

  registerBlock( 'denver17/events-band', {
    edit: function ( props ) {
      var attrs = props.attributes;
      var set   = props.setAttributes;
      var blockProps = useBlockProps();

      return el( Fragment, null,
        el( InspectorControls, null,
          el( PanelBody, { title: 'Events Band', initialOpen: true },
            el( TextControl, {
              label: 'Section heading',
              value: attrs.sectionHeading,
              onChange: function ( v ) { set( { sectionHeading: v } ); }
            } ),
            el( 'p', { style: { fontSize: '12px', color: '#757575', marginTop: '8px' } },
              'Event cards are static placeholders until the events plugin is live (Session 6). Images and event details will be managed through the plugin at that point.'
            )
          )
        ),
        el( 'div', blockProps,
          canvasPlaceholder( 'Events Band', attrs.sectionHeading )
        )
      );
    },
    save: function () { return null; }
  } );

  // ---------------------------------------------------------------------------
  // denver17/cta-band
  // ---------------------------------------------------------------------------

  registerBlock( 'denver17/cta-band', {
    edit: function ( props ) {
      var attrs = props.attributes;
      var set   = props.setAttributes;
      var blockProps = useBlockProps();

      return el( Fragment, null,
        el( InspectorControls, null,
          el( PanelBody, { title: 'CTA Band', initialOpen: true },
            el( TextControl, {
              label: 'Eyebrow',
              value: attrs.eyebrow,
              onChange: function ( v ) { set( { eyebrow: v } ); }
            } ),
            el( TextControl, {
              label: 'Heading',
              value: attrs.heading,
              onChange: function ( v ) { set( { heading: v } ); }
            } ),
            el( TextControl, {
              label: 'Button text',
              value: attrs.buttonText,
              onChange: function ( v ) { set( { buttonText: v } ); }
            } ),
            el( TextControl, {
              label: 'Button URL',
              value: attrs.buttonUrl,
              type: 'url',
              onChange: function ( v ) { set( { buttonUrl: v } ); }
            } )
          )
        ),
        el( 'div', blockProps,
          canvasPlaceholder( 'CTA Band', attrs.heading )
        )
      );
    },
    save: function () { return null; }
  } );

  // ---------------------------------------------------------------------------
  // denver17/hours-display
  // ---------------------------------------------------------------------------

  var ToggleControl = wp.components.ToggleControl;

  registerBlock( 'denver17/hours-display', {
    title: 'Hours Display',
    category: 'denver17',
    description: 'Live hours pulled from Google Sheets. All sections can be toggled.',
    // Must mirror block.json's supports — this block registers client-side by
    // hand (no build step, no editorScript in block.json), so the server's
    // metadata never reaches the editor. Without `align` here the toolbar
    // width control never appears, even though the PHP side honors it.
    supports: {
      html: false,
      className: false,
      anchor: true,
      align: [ 'wide', 'full' ],
    },
    attributes: {
      align:         { type: 'string' },
      heading:       { type: 'string',  default: '' },
      showStatus:    { type: 'boolean', default: true },
      showSpecial:   { type: 'boolean', default: true },
      showBaseHours: { type: 'boolean', default: true },
      showNote:      { type: 'boolean', default: true },
    },
    edit: function ( props ) {
      var attrs      = props.attributes;
      var set        = props.setAttributes;
      var blockProps = useBlockProps();

      return el( Fragment, null,
        el( InspectorControls, null,
          el( PanelBody, { title: 'Content', initialOpen: true },
            el( TextControl, {
              label: 'Optional heading',
              help:  'Leave blank to show no heading above the hours block.',
              value: attrs.heading,
              onChange: function ( v ) { set( { heading: v } ); }
            } )
          ),
          el( PanelBody, { title: 'Sections', initialOpen: true },
            el( ToggleControl, {
              label:    'Show open/closed status',
              checked:  attrs.showStatus,
              onChange: function ( v ) { set( { showStatus: v } ); }
            } ),
            el( ToggleControl, {
              label:    'Show special notice',
              help:     'The notice from column D of the Schedule tab.',
              checked:  attrs.showSpecial,
              onChange: function ( v ) { set( { showSpecial: v } ); }
            } ),
            el( ToggleControl, {
              label:    'Show base hours',
              help:     'The regular weekly schedule lines from the Base Hours tab.',
              checked:  attrs.showBaseHours,
              onChange: function ( v ) { set( { showBaseHours: v } ); }
            } ),
            el( ToggleControl, {
              label:    'Show "hours subject to change" note',
              checked:  attrs.showNote,
              onChange: function ( v ) { set( { showNote: v } ); }
            } )
          )
        ),
        el( 'div', blockProps,
          canvasPlaceholder( 'Hours Display', 'Live from Google Sheets · renders on front end' )
        )
      );
    },
    save: function () { return null; }
  } );

  // ---------------------------------------------------------------------------
  // denver17/beer-list
  // ---------------------------------------------------------------------------

  registerBlock( 'denver17/beer-list', {
    title: 'Beer List',
    category: 'denver17',
    description: 'Live tap list from Google Sheets.',
    // Mirrors block.json — see the note on denver17/hours-display above.
    supports: {
      html: false,
      className: false,
      anchor: true,
      align: [ 'wide', 'full' ],
    },
    attributes: {
      align:           { type: 'string' },
      variant:         { type: 'string',  default: 'band' },
      backgroundImage: { type: 'object',  default: {} },
      eyebrow:         { type: 'string',  default: 'The Jolly Corks Bar' },
      heading:         { type: 'string',  default: 'On Tap' },
      note:            { type: 'string',  default: 'Updated live from behind the bar.' },
      showStyle:       { type: 'boolean', default: true },
      showAbv:         { type: 'boolean', default: true },
      showComingSoon:  { type: 'boolean', default: true },
    },
    edit: function ( props ) {
      var attrs      = props.attributes;
      var set        = props.setAttributes;
      var blockProps = useBlockProps();
      var isBand     = attrs.variant !== 'plain';

      return el( Fragment, null,
        el( InspectorControls, null,
          el( PanelBody, { title: 'Style', initialOpen: true },
            el( SelectControl, {
              label: 'Variant',
              value: attrs.variant,
              options: [
                { label: 'Band (dark, full-bleed)', value: 'band' },
                { label: 'Plain (light, inline)',   value: 'plain' }
              ],
              onChange: function ( v ) { set( { variant: v } ); }
            } ),
            isBand
              ? el( 'p', { style: { fontSize: '12px', color: '#757575', margin: '4px 0 16px' } },
                  'Set the block to Full width in the toolbar so the band reaches both edges.'
                )
              : null,
            isBand
              ? imagePicker( attrs.backgroundImage, 'Background photo (optional)', function ( img ) {
                  set( { backgroundImage: img } );
                } )
              : null,
            isBand
              ? el( 'p', { style: { fontSize: '12px', color: '#757575' } },
                  'Use the back-bar stained glass or another shot of the room. Do not use a photo of the taps — the list is live and a tap photo goes out of date the moment a keg changes.'
                )
              : null
          ),
          el( PanelBody, { title: 'Content', initialOpen: true },
            el( TextControl, {
              label: 'Eyebrow',
              help:  'Small line above the heading. Leave blank to hide.',
              value: attrs.eyebrow,
              onChange: function ( v ) { set( { eyebrow: v } ); }
            } ),
            el( TextControl, {
              label: 'Heading',
              help:  'Displayed above the tap list. Leave blank to hide.',
              value: attrs.heading,
              onChange: function ( v ) { set( { heading: v } ); }
            } ),
            el( TextControl, {
              label: 'Live note',
              help:  'Shown next to a pulsing dot. Leave blank to hide both.',
              value: attrs.note,
              onChange: function ( v ) { set( { note: v } ); }
            } )
          ),
          el( PanelBody, { title: 'Display', initialOpen: false },
            el( ToggleControl, {
              label:    'Show beer style',
              checked:  attrs.showStyle,
              onChange: function ( v ) { set( { showStyle: v } ); }
            } ),
            el( ToggleControl, {
              label:    'Show ABV',
              checked:  attrs.showAbv,
              onChange: function ( v ) { set( { showAbv: v } ); }
            } ),
            el( ToggleControl, {
              label:    'Show coming soon section',
              checked:  attrs.showComingSoon,
              onChange: function ( v ) { set( { showComingSoon: v } ); }
            } )
          )
        ),
        el( 'div', blockProps,
          canvasPlaceholder(
            'Beer List — ' + ( isBand ? 'Band' : 'Plain' ),
            'Live from Google Sheets · renders on front end'
          )
        )
      );
    },
    save: function () { return null; }
  } );

} )();
