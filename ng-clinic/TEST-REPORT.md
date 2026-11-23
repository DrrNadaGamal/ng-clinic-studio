# NG Clinic Studio 1.0.0 verification

23 November 2026. **105 automated checks passed.**
Environment: WordPress 7.1.5, PHP 8.3.36, SQLite integration in WordPress Playground;
headless Google Chrome. Test data is synthetic. Fresh installation for each run.

- Activation and seed: 12 fictional profiles, 12 visits, three doctors, two services.
- Booking: actual browser submission, confirmation reference, database persistence,
  public booking, consent validation, server-side date/time/doctor/service validation.
- Duplicate doctor/time rejected with HTTP 409; no extra patient record created.
- Status transitions: check-in, completion, cancellation, terminal-state protection,
  cancellation releases the slot for another booking.
- Patient notes: saved, sanitized; oversized and missing records rejected.
- Permissions: administrator and clinician access; receptionist can manage visits
  but cannot read or write notes; subscriber and anonymous staff access denied.
- Security controls exercised: invalid booking and REST nonces, invalid inputs,
  no patient details in public catalog; PHP parsing of all five PHP files.
- 50 staff page-overflow checks: five screens, English and Arabic, at widths
  1440, 1024, 768, 390 and 320px. Eight booking page-overflow checks: English and
  Arabic at 1440, 768, 390 and 320px. Real mobile and desktop screenshots captured.
- WordPress page containing the real shortcode renders the booking form.
- No browser JavaScript errors during the test suite.

Manual visual checks: English desktop overview, Arabic desktop overview, English
and Arabic mobile overviews, English mobile booking and booking confirmation.
Mobile navigation was changed to wrap, keeping every screen reachable. Mobile
visit times and dates were separated to avoid crowding in RTL.

## Limits of these results

No claim of production readiness, healthcare compliance or a full penetration test.
The database-unique-key path was exercised with duplicate requests; simultaneous
multi-process load was not stress-tested. MySQL/MariaDB, actual Elementor editor,
Polylang, multisite, screen readers and the uninstall path were not runtime-tested.
The shortcode itself is tested, not the Elementor host widget.

Notification delivery is deliberately absent. Activity log entries are simulations.
The plugin keeps no email addresses or phone numbers. Demo notes are unencrypted.
Test sites and test users exist only in an isolated disposable environment.

Full automated test log and reproducible harness are in the separate source ZIP.
