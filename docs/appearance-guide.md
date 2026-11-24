# Theme and Density preferences

Anonymous selection is an administrator opt-in on Papi Jo H5P Themes, disabled by
default. When enabled, H5P appearance appears above the first eligible activity.
The disclosure starts collapsed on each page; saved preferences apply immediately.
Theme, Density and Restore defaults control all admitted activities on the page.
Only eligible built-ins and published custom Themes are offered alongside Site default.
Unavailable saved custom Themes fall back to Site default without changing Density.

Anonymous choices use site-scoped localStorage in the current browser/profile:
`pjh5pwp:anonymous:v1:<encoded canonical home>:theme` and `:density`.
Private browsing has its own storage lifetime. If storage is blocked, choices work
for the current page only. Restore defaults removes these two keys and chooses
Site default / Original. Disclosure expansion is never persisted. No standalone
companion preferences are imported, and no cookies are added for preferences.

Signed-in users continue using their own WordPress Profile preferences when permitted
by role (administrators are allowed automatically). These remain database-based and
are independent of anonymous browser choices. Login/logout transitions request a
manual reload; activities are not reloaded for normal selection or renewal.

H5P Core defaults is an internal mode and administrator choice, but is no longer
selectable as a new anonymous/Profile preference. An existing saved Core choice is
shown as a disabled saved-preference entry and keeps working. Changing Density or
saving unrelated Profile fields preserves it. Explicitly choosing another Theme
replaces it; Restore defaults deliberately resets anonymous choices.

Routine success status is visually hidden while collapsed, with accessible status
announcements retained. Important warnings/errors and reload instructions remain
visible. The fieldset legend remains semantic and accessible but visually hidden.

Active SNORDIAN suspends Papi Jo playback and Profile controls. Saved preferences
are retained; Theme administration and transfer remain available. Deactivate the
standalone Anonymous companion before using integrated 0.3.0.
