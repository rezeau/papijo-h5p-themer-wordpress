# Phase 1C-C — ordered Firefox acceptance checklist

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

**Status: NOT YET TESTED.** Use your normal Firefox profile at unchanged
https://acceptance.example.test. Record Firefox version and PASS/FAIL for each step. No new
certificate exception, security-setting change or SNORDIAN deactivation is needed.

Starting state from saved verification: plugin active; storage ready; global
**H5P Core defaults**; five built-ins; zero custom themes; SNORDIAN active with
Daylight/large; Papi Jo runtime suspended. Do not initialize storage again.

1. **Open administration.** In normal Firefox, enter the URL below and sign in
   with your existing administrator account if needed. Confirm H5P Content →
   Papi Jo H5P Themes, the overview and SNORDIAN suspension warning.

   ```text
   https://acceptance.example.test/wp-admin/admin.php?page=pjh5pwp-themes
   ```

2. **View built-ins.** Confirm Daylight, Lavender, Mint, Sunset and Dark, plus
   distinct Core defaults. Open each View/preview; built-ins must have no editable
   definition or Delete action. Expand the original-Core comparison.

3. **Create a disposable custom.** Use Add custom theme with a unique identifier
   such as `firefox_acceptance_a` and a clear test name. Save and reload; it must
   start unpublished. Record its pinned inheritance and permanent identifier.

4. **Edit colors and inheritance.** Expand the five groups using keyboard/mouse.
   Test a native picker and #RGB/#RRGGBB text, save and reload. Clear an override
   and save: its inherited value must return. Invalid text must show an error
   without saving it. Keep all changes confined to your disposable theme.

5. **Check saved/live previews.** On custom Edit, change a valid color without
   saving; the live sample should update. Invalid text should retain the last valid
   sample color; blank should inherit. Reset should restore initial fields/colors.
   Save/reload and compare the saved sample with Core. No Density preview controls.

6. **Duplicate and rename.** Duplicate Mint into a second disposable custom such
   as `firefox_acceptance_b`, save, then edit. Rename through Name; its identifier
   must stay fixed. If duplicating your first custom, confirm the copy is independent
   and retains the preset pin rather than becoming its live child.

7. **Test publication.** Publish and unpublish only your disposable custom through
   Save learner visibility; reload after each. Avoid changing existing built-in
   visibility. Changing publication must not change the global default.

8. **Check stale tabs/access and presentation.** Edit one in two tabs: save one, then submit the older form. Expect a conflict
   and copyable edits, never a silent overwrite. Refresh after success must not
   repeat POST. Check narrow layout, labels, keyboard focus and status/error messages.
   With an existing nonadministrator/logged-out session, direct admin access must
   be denied. Do not create accounts or change roles for this checklist.

9. **Test global default/deletion protection.** Select your unpublished custom as
   global default and save/reload. Deleting it must be blocked. Restore **H5P Core
   defaults**, then delete only the disposable themes you created, confirming each.
   This tests stored administration settings; Papi Jo playback styling stays suspended.

10. **Check SNORDIAN coexistence.** Inspect Settings → H5P Themer without saving;
    its existing Daylight/large configuration must remain. View existing official
    and Papi Jo activities without editing content or submitting answers; styling
    and Density should be unchanged, with no new Papi Jo activity selectors. Record
    any new console/PHP errors. Do not deactivate SNORDIAN or alter its settings.

Final manual-test state should return to Core default, no disposable customs and unchanged built-in
publication/SNORDIAN configuration. Use domain forms for cleanup, never a database
reset or backup restore. If an unexpected failure prevents safe cleanup, stop and
report the remaining state.

Send the Firefox version, step number, expected/actual behavior and reproduction
steps for each FAIL. All unchecked/manual results remain NOT YET TESTED until you
report them. The next development phase will wait for your acceptance results.