# ArtisanPack UI — Design System

> The handcrafted component kit for makers who ship. *Pack it beautifully.*

ArtisanPack UI is a website / landing-page component library with an **atmospheric, space-inspired** feel — neon colours popping off deep space-black grounds. It is **dark-mode first**: the default surface is `#111827` (section `#1F2937`, deepest ink `#0B1120`) lifted with neon gradients and glows. A `data-theme="light"` opt-in flips the whole subtree to a light theme. Headings + body are **Poppins**; code is **Space Mono**.

It is a Jacob Martella Web Development (JMWD) project.

## Sources
- **Figma:** *ArtisanPack Website.fig* (mounted read-only when the `fig_*` tools are available). Pages observed: Brand-Assets, Typography, UI-Elements, Components, Patterns (the full website block library), Wireframes, Mockups, and status boards. The full file inventory is 142 component families × 3 themes.
- Logo, icon and illustration PNGs were extracted from Brand-Assets and live in `assets/`.

## Figma family coverage — how the 157 families map

The Figma importer counts **157 families** (142 sets + 15 standalone). A line-by-line audit against the built tree shows **every genuinely distinct product component family is built** — the counter's residual is duplicates, internal scaffolding, section-patterns, and assets, none of which are buildable as standalone primitives:

- **Primitives** (`components/<group>/`) — the reusable atoms. **71 built** (see index below). This now covers every distinct atom the kit defines: forms, fields, typography (incl. `SmallTitle`, `Spacer`), layout (`Columns`, `Tabs`, `Accordion`), data (`Table`, `List`), media (`Gallery`, `Podcast`, `Browser`), nav, commerce, glyphs (`Checkmark`, `Warning`, `X`), `Search`, `SearchFilters`, `TextButton`.
- **Patterns** (`components/patterns/`) — the `01.–15.` families are *section-level compositions* (Header, Features, Pricing, Testimonials, FAQ, CTA, BlogPosts, Portfolio, PostContent, ContactForm, LoginForm, RegisterForm, Footer). **13 built**; remaining variants (Content layouts, Ecommerce Checkout, Footer sub-variants, carousels/sliders) are added as the pages that need them are built.
- **Templates** (`templates/`, currently in `_archive/` pending the reset) — full pages assembled from patterns.

**Why the counter reads ~71 of 157 and won't reach parity** — the ~86 difference is entirely non-primitive:
- **Duplicate listings** — the kit places the same family on multiple pages, so the importer counts it 2–3×: Avatar×2, Blockquote×2, Feature×2, **Media×3**, Pagination×2, Rating×2, Section Title×2, Heading×2, Input×2, Paragraph×2, Payments×2, Select×2, **Social×3**, Spacer×2, Checkmark×2, Table/Cell×2, X×2, Warning, Accordion/Tab×2 (≈25 phantom entries).
- **`_Master/*`** (Button, End, Start, Input Field, Input Value, Tab, Validation ×2 each) — Figma *master* components our primitives are instances of; not separate UI.
- **`_DesignKit/*`** (Section Title, Color, Section Heading, Subheading, Typography Row, Separator, How To Heading) — the kit's own **specimen/spec frames**, not product components.
- **Glyph sub-parts** — `Start`, `End`, `Input Value`, `Table / Cell`, `Checkmark`, `X`, `Warning` are parts rendered *inside* their parents (Input, Table, Checkbox, Alert).
- **Numbered section-patterns** — the `01.–15.` variants live in the patterns layer above, not as primitives.
- **Assets/specimens** — `Buttons`, `Button/Secondary/…`, `Icon/Default`, `Icon/White`, `Logo/Default`, `Logo/White`, `Table / Small Table` are instance specimens / logo + icon assets (in `assets/`), not components.

**Confirmed intentional additions** (standard UI the plan calls for, kept under their conventional names rather than the kit's layer names):
- `Toggle` — matches the kit family **Toggle** (renamed from the earlier `Switch`).
- `Signup` — matches the kit family **Signup** / **Email Form** (renamed from the earlier `Newsletter`).
- `Tabs` — covers the kit's **Tab Menu** + **Tab Groups** families (underline + pill variants).
- `Alert`, `Badge`, `Tag`, `Card`, `TestimonialCard`, `CopyToClipboard` — conventional UI with no 1:1 kit family; added deliberately.
- **Section patterns** named by convention rather than the kit's numbered layer names: `Header` (`01. Header`), `Features` (`03. Features`), `Testimonials` (`07. Testimonial`), `FAQ` (`10. FAQ`), `CallToAction` (`06. CTA`), `ContactForm` (`11. Contact Form`), `LoginForm` (`12. Login Form`), `RegisterForm` (`13. Register Form`), `Footer` (`15. Footer`). `Pricing`, `BlogPosts`, `Portfolio`, `PostContent` match the kit's standalone family names directly.

> The automated importer counts primitives vs 157 and will keep reporting "< 157 built"; that is expected given this layered architecture. Coverage is tracked against the **plan's** component list, not a 1:1 primitive-per-family mapping.

## What's here (manifest)

### Tokens — `styles.css` → `tokens/`
- `tokens/fonts.css` — Poppins + Space Mono (Google Fonts).
- `tokens/colors.css` — dark-first neon triad, neutral ramp, semantic colours, the **six gradient combos** + signature neon sweep, and **neon glow** tokens. Two themes (**dark default**, `data-theme="light"` override).
- `tokens/typography.css` — full Poppins type scale + Space Mono, `.ap-*` helper classes.
- `tokens/spacing.css` — spacing, radius, the **box-design** language (`--box-radius 12`, `--box-border-width 3`, `--btn-radius 5`, `--field-radius 5`), shadow, layout & motion.
- `tokens/base.css` — helpers: `.ap-gradient-text`, `.ap-box` + `.ap-border-{primary,secondary,accent,gradient}`, `.ap-glow-*`, and the atmospheric `.ap-space` / `.ap-starfield` / `.ap-aurora` / `.ap-divider-neon`.

### Components — `components/<group>/`
- `forms/` — **Button**, **TextButton**, **Input**, **Textarea**, **Select**, **Search**, **Signup**, **Checkbox**, **Radio**, **Toggle**
- `feedback/` — **Badge**, **Tag**, **Alert**, **Rating**
- `layout/` — **Card**, **Avatar**, **Accordion**, **AccordionTab**, **Tabs**, **Pagination**, **Columns**
- `typography/` — **Heading**, **Paragraph**, **SmallTitle**, **Spacer**
- `content/` — **SectionTitle**, **PageHeader**, **Blockquote**, **PostMeta**
- `fields/` — **Email**, **Phone**, **Username**, **Password**, **Website**, **Date** (Input presets)
- `nav/` — **Navigation**, **Icon**, **Social**
- `glyphs/` — **Checkmark**, **X**, **Warning** (Style/Weight/Shape icon glyphs)
- `media/` — **Browser**, **Gallery**, **Podcast**
- `commerce/` — **Payments**, **CreditCard**, **Product**, **ProductImage**, **ProductListing**
- `marketing/` — **Feature**, **Media**, **Cover**, **PricingCard**, **TestimonialCard**
- `patterns/` — **Header**, **Features**, **Pricing**, **Testimonials**, **FAQ**, **CallToAction**, **BlogPosts**, **Portfolio**, **PostContent**, **ContactForm**, **LoginForm**, **RegisterForm**, **Footer** (section-level compositions built from the primitives)
- `patterns/` **Heroes** — **HeroCentered**, **HeroSplit**, **HeroPackage**, **HeroImage**, **HeroStats**, **HeroTerminal** (content/hero sections for homepage + single-package pages; all support action buttons and a click-to-copy install command)
- `patterns/` **Page Headers** — **PageHeaderCentered** (centered aurora, default), **PageHeaderEditorial** (breadcrumb + flush-left title + neon rule), **PageHeaderDisplay** (oversized gradient title on starfield), **PageHeaderCompact** (breadcrumb + inline title + actions bar), **PageHeaderBoxed** (eyebrow + title + lead in a gradient-border glass box) — the band atop inner pages; the simpler `content/PageHeader` remains as the minimal base band
- `patterns/` **Features sections** — base grids demo the parametric `Features` component (**2×2**, **3×3**, **3×2** = `columns` + item count), plus layout variants: **FeaturesSideTitle** (heading pinned beside the grid), **FeaturesAltRows** (alternating Feature + Media rows), **FeaturesLeadMedia** (hero media banner + feature row), **FeaturesBento** (large Media tile + 2 stacked + 3 across), **FeaturesCards** (image-topped feature cards). Every cell is the standard `Feature` block; images use the `Media` frame (`fill` for spanning tiles)
- `patterns/` **Testimonials sections** — base grids demo the parametric `Testimonials` component (**2-up**, **3-up** = `columns`), plus layout variants: **TestimonialsWall** (masonry wall), **TestimonialsSideTitle** (heading pinned beside the grid), **TestimonialsSpotlight** (single large centered quote), **TestimonialsSideImage** (featured quote beside a portrait `Media`), **TestimonialsOverlap** (review card overlapping a photo banner), **TestimonialsCarousel** (horizontal scroll strip). Every review is the standard `TestimonialCard`; portraits use `Avatar`, images use `Media`
- `patterns/` **Text & Media sections** — text paired with one or many images (kit "02. Content"): **TextMediaSplit** (50/50 text · image, `flip`), **TextMediaBanner** (centered intro + wide banner), **TextMediaGrid** (text + image grid), **TextMediaGallery** (centered intro + image row), **TextMediaBentoText** (text as a bento tile), **TextMediaCollage** (two overlapping images), **TextMediaBento** (centered intro + mixed bento), **TextMediaStacked** (text + stacked images), **TextMediaSpotlight** (dominant image + narrow text/thumbs). Text uses `SectionTitle` + `Button` (shared `_textmedia/textmedia-shared.jsx` helpers); images use the `Media` frame
- `patterns/` **Call to Action** — the base `CallToAction` (solid-gradient box) plus a suite: button CTAs **CtaSimple** (centered on starfield), **CtaInline** (title left, buttons right bar), **CtaWideImage** (copy + button + image), **CtaGradientBox** (dark box, gradient border + glow); and newsletter CTAs **CtaSignupCentered**, **CtaSignupSplit**, **CtaSignupGradient** (bright gradient panel), **CtaSignupImage**. Buttons use `Button`, email capture uses `Signup`, images use `Media` (shared `_cta/cta-shared.jsx` helpers)
- `patterns/` **Pricing sections** — the base `Pricing` (3-up boxes) plus variants: **PricingTwo** (two wide plans), **PricingFour** (four compact tiers), **PricingToggle** (monthly/annual switch), **PricingSpotlight** (featured plan enlarged & centered), **PricingComparison** (full feature matrix, plans as columns). Plans use the `PricingCard` primitive; CTAs use `Button`
- `patterns/` **Packages sections** — the base `Portfolio` (icon cards) plus variants (kit "Portfolio"): **PackagesInstall** (cards + copy-install command), **PackagesMeta** (version badge + tags + View link), **PackagesFeatured** (flagship panel + list), **PackagesList** (horizontal rows — good for related packages), **PackagesCompact** (compact linked cards — related strip), **PackagesBento** (hero package tile + smaller), **PackagesLarge** (two big cards with meta/tags/buttons), **PackagesStats** (installs/version/license strip). Cards build on `Feature` + `CopyToClipboard`/`Badge`/`Tag`/`Button` (shared `_packages/packages-shared.jsx` data + helpers)
- `patterns/` **Blog sections** — the base `BlogPosts` plus variants (kit "08. Blog" / "Blog Posts Featured and Latest"): **BlogGrid** (card grid, `columns` for 2-up/3-up), **BlogFeatured** (featured post + latest list), **BlogRows** (horizontal rows), **BlogOverlay** (title over a full-bleed cover), **BlogList** (minimal image-free list), **BlogSideHeading** (heading pinned left + grid), **BlogMagazine** (one big featured + two small). Covers use the `Media` frame; category uses `Badge`, byline uses `Avatar` (shared `_blog/blog-shared.jsx` data + card helpers)
- `patterns/` **FAQ sections** — the base `FAQ` (full-width list) plus variants (kit "10. FAQ"): **FaqTwoColumn** (two-column accordion), **FaqSideHeading** (heading + CTA left, accordion right), **FaqCategorized** (grouped by category), **FaqOpen** (all answers shown, no toggles), **FaqBoxed** (coloured-border accordion boxes), **FaqSearch** (live search field above the list), **FaqSupport** (accordion + support card), **FaqNumbered** (numbered Q&A cards). Questions use the `AccordionTab` primitive (shared `_faq/faq-shared.jsx` data)
- `data/` — **Table**, **List**
- `utility/` — **CopyToClipboard**, **SearchFilters**
- `lib/box.js` — `boxDecor()` helper resolving the shared `border` / `glow` box-design props (not a component).

Many boxed components take `border` (`none`/`primary`/`secondary`/`accent`/`gradient`) and `glow` (`none`/`primary`/`secondary`/`accent`/`gradient`) props for the box-design language. All exported on `window.ArtisanPackUIDesignSystem_151903`. Each directory has a `@dsCard` demo HTML.

### UI kit — `ui_kits/website/`
Full interactive marketing homepage composed from the components — dark, neon-led. (From the pre-reset build; will be refreshed to the new atmospheric spec during the pages phase.)

### Templates — being rebuilt
The previous 26 `.dc.html` templates (Montserrat, light-first origins) are parked in **`_archive/templates/`** for reference. New templates matching the reset (Poppins, dark-first, box-design, atmospheric) will be built in the **pages** phase, after components + patterns are approved.

### Foundation cards — `cards/`
Specimen cards for the Design System tab — **Colors** (neon core, gradients, semantic, themes, neutral ramp), **Type** (display, body, mono, eyebrow), **Spacing** (scale, radius, box design, glows), **Brand** (logo, icon & atmosphere).

### Assets — `assets/`
`logo/` (wordmark light + reverse, app icon), `illustrations/`.

---

## CONTENT FUNDAMENTALS

**Voice:** confident, warm, maker-to-maker. Speaks to *you* ("Ship beautiful sites", "Pack your next site today"). Active, imperative CTAs. Light craft metaphor — *pack, artisan, handcrafted, blocks* — never overdone.

**Tone:** energetic but plain-spoken. Short declaratives. Benefit-first ("Light mode was genuinely one line"). Avoids enterprise jargon and hype words.

**Casing:** Sentence case for headings and buttons ("Start free", "Get Pro"). UPPERCASE only for eyebrows/labels with wide tracking (`.14em`).

**Numbers & proof:** concrete, specific ("142 blocks", "200+ components", "12,400+ makers"). Prices shown as `$12/mo`. Never vague.

**Punctuation:** em-dashes and middots (·) for rhythm. Occasional ✓ check. Tagline closes with a period. No exclamation overload.

**Emoji:** not used in product copy. Status uses ✓ / dots, not emoji.

**Example strings:** "Ship beautiful sites, artfully packed." · "v2.4 — 142 blocks shipped" · "Free forever for side projects. No credit card required."

---

## VISUAL FOUNDATIONS

**Theme model.** Dark is the default and the marketing context. `data-theme="light"` is the explicit opt-in for embeds / docs / app contexts that need it. Always style from semantic `--color-*` aliases, never raw `--ap-*` hex, so theming works.

**Colour.** Neon triad over space-black grounds: **Primary blue `#2962FF`**, **Secondary cyan `#00E5FF`**, **Accent magenta `#E040FB`** (dark theme). Grounds: `--color-base #111827` (page), `--color-section #1F2937` (section/card), `--ap-ink #0B1120` (deepest void). Text is white with muted `#9CA3AF` / subtle `#6B7280`. Light theme swaps to deeper hues (primary `#1A237E`, secondary `#00838F`, accent `#8E24AA`). Validation uses Tailwind green-500 `#22C55E` / red-500 `#EF4444`.

**Gradients & glows.** Six named combos (`--grad-primary-secondary` … `--grad-accent-secondary`) plus a signature three-stop **neon sweep** (`--grad-neon`, blue→cyan→lilac). Used liberally per the brand — wordmark, primary CTAs, active toggles/tabs, checkbox/switch fills, icon tiles, featured pricing card, CTA panels. Neon **glows** (`--glow-primary/secondary/accent/gradient`) add the outer bloom that makes elements "pop" off the dark ground.

**Atmosphere (space).** Sections can sit on `.ap-starfield` (faint dotted star layer over `--ap-ink`) or use `.ap-aurora` (large blurred neon nebula blobs behind content). `.ap-divider-neon` is a gradient hairline. Keep it tasteful — atmosphere behind heroes and section transitions, not everywhere at once.

**Type.** **Poppins** for display, headings, body and UI — geometric, friendly, round. **Space Mono** for code, commands, package names and technical metadata. Headings tightened (`-2%` tracking); h1 56 / 700, h2 44 / 700 down to body 16 / 400 at 165% leading. Eyebrows 13px uppercase, `0.16em` tracking. Display lines use `text-wrap: balance`.

**Backgrounds.** Mostly the ink ground with section-rhythm via lifted `--color-section` panels. Accent via **radial blue/magenta glows** (~22% alpha) behind the hero, and the gradient closing panel. No textures or noise. Imagery is product mocks framed in window chrome.

**Box design.** The core surface language: `12px` radius, `3px` border. Border options are `primary` / `secondary` / `accent` / a neon `gradient` border (via padding-box/border-box layering); glow options are the neon glows or none. Buttons and form fields are crisper at `5px`. Cards lift on hover with a neon or neutral shadow.

**Corners.** Small and crisp: buttons `3–5px`, inputs `4–5px`, cards `10px`, big panels `16–24px`, pills `999px`.

**Borders & shadows.** Hairline borders are `rgba(255,255,255,0.10)` on dark, `--ap-gray-300` on light. The shadow scale is heavier on dark (`sm` = `0 1px 3px rgba(0,0,0,0.45)`) so layering still reads against the ink ground; `--shadow-panel` is the DesignKit hero shadow; `--shadow-focus` is a blue ring.

**Motion.** Quick, eased — `--dur-fast 120ms` / `--dur-base 200ms` on `--ease-out`. Buttons lift on hover, scale `0.98` on press. Accordions animate `grid-template-rows`. No bounces, no infinite loops.

**Hover / press.** Hover = subtle lift + `brightness(1.06)` on filled buttons; light wash on hollow (surface-2 in dark, gray-100 in light). Press = settle to `scale(0.98)`. Nav links shift from muted → text.

**Transparency & blur.** Sticky header uses `rgba(10,14,39,0.72)` with `backdrop-filter: blur(14px)` in dark mode. Tint badges use `~14%` alpha of their semantic hue.

**Layout.** Centred content on a `1320px` wide rail (`1200px` default), `24px` gutters. Section rhythm ~`96px`. Section headers centred (eyebrow → title → lead); content blocks left-aligned with a top icon.

**Imagery vibe.** Cool, crisp, screen-first — product UI mocks in window frames, not lifestyle photography.

---

## ICONOGRAPHY

The Figma uses **Font Awesome** (5 & 6, Solid / Regular / Brands) as its icon system. This system follows that: link **Font Awesome 6** from CDN and use `fa-solid` / `fa-regular` / `fa-brands` classes. UI affordances lean on a handful of glyphs (`fa-arrow-right` on CTAs, `fa-check` in lists, `fa-chevron-*` for disclosure). Brand/social icons use `fa-brands`. In-component glyphs (checkbox tick, accordion +, rating star, select caret) are drawn inline as SVG so they inherit the gradient — intentional, not Font Awesome. **No emoji** as icons.

> **Font note (resolved):** Poppins + Space Mono load from Google Fonts. If you have licensed/self-hosted copies, drop them in `assets/fonts/` and swap the `@import` in `tokens/fonts.css` for `@font-face` rules.

## Conventions
- Style from `--color-*` aliases; never hard-code hex. Use `--ap-*` only for the gradient and the constant brand swatches in cards.
- One gradient moment per view — the primary action / featured plan / hero glow.
- Sentence case, specific numbers, no emoji.
- Compose UI kits and pages from `components/` primitives; don't re-implement them.
- Dark is default — only wrap in `data-theme="light"` when the embed/context demands light.
