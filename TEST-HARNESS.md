# Clinic Studio source and verification

The installable plugin lives in `ng-clinic/`. Use the separate small installable
ZIP for WordPress, not this source bundle. Read that directory's README and safety
notes first. No real clinic information, tokens, test cookies or accounts included.

## Optional local test

Requires Node 22+, npm, internet for initial packages/WordPress download, and
Google Chrome. Run from this source-bundle directory:

```
npm ci
npm test
```

Default Chrome executable is `/usr/bin/google-chrome`. On another OS/path, set
`CHROME_PATH` to your installed Chrome executable. This harness starts a disposable
WordPress Playground server at 127.0.0.1:9400 and closes it after verification.
No WordPress server or PHP installation is required. It creates synthetic test
users and temporary authentication fixtures only inside the disposable local
run. Fixtures are removed at the end. Never expose this test server to a network
or deploy the verification directory or its generated files. Port 9400 must be free.

Results and actual screenshots are written in `screenshots/`. Test setup requires
a writable `ng-clinic/tests/` folder. The installable ZIP excludes all test fixtures.
Playground/Playwright are development dependencies, not plugin dependencies.

The test suite is destructive only to its disposable WordPress instance. Do not
change the mount or server URL to an existing WordPress website. The included
report records the tested versions and limitations.
