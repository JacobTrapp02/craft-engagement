# Release Notes for Engagement
## 1.0.2 - 2026-08-07
- Added opt-in `mode: 'deferred'` rendering for Blitz-safe Ratings, Likes, and Favorites widgets.
- Added the external `Engagement.init(root)` initializer and the bubbling `engagement:ready` browser event.
- Added `craft.engagement.registerDeferredAssets()` for loading the initializer from a cached outer template.
- Deferred widgets now carry token-free client configuration and fetch a fresh session CSRF token only when an allowed interaction is submitted.
- HTML and CSS template overrides run in normal and deferred modes; JS overrides remain unchanged in normal mode and are intentionally omitted in deferred mode.

## 1.0.1 - 2026-04-24

### Fixed
- Fixed entry editor icon mode panel visibility in side-panel editing contexts for Ratings, Likes, and Favorites (emoji/custom icon sections now reliably show and hide when switching modesas well as color selections).

### Improved
- Improved plugin settings and field settings help text.
- Added documentation links and clearer tooltip messaging for guest interactions, icon modes, editor overrides, and widget previews.
- Changed Default Enabled State for the 3 fields to true
- Accessibility Updates for front end rendered widgets
