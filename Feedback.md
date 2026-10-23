All phases are built and committed on main (180 commits, clean working tree, nothing pushed). Treat it as unverified code, though. Everything from P2.4 onward was built without running tests, linters or browser/device checks, as you asked. P2.9, the end-to-end and race-test milestone, was skipped and its tests are deferred.

Phase	Result
0 Foundation, 1 Core platform, P2.1–P2.3	Built, with passing test suites at the time
P2.4–P2.8 (negotiation, trip lifecycle, customer and driver ride flows, admin ops)	Built, untested
3 Money and support	Built, untested
4 Courier, moving, freight	Built, untested
5 Operations and website	Built, untested
6 Hardening and release	Built, untested
What I'd expect from the first full run: scripts/verify-all.sh will probably fail on pint, phpstan and Dart analysis in the code written since P2.4. Fix those first, then work through the tests in the order given in docs/RELEASE_REPORT.md §9.

Where to find things

MISSED_TESTS.md is the 1,287-line backlog of every deferred test.
docs/RELEASE_REPORT.md has the milestone table, credentials checklist, known limitations and verification commands.
scripts/verify-all.sh runs every check in order and writes verify-results/SUMMARY.md.
CI workflows are in .github/workflows/.
Known gaps

Realtime, location and push have not been tested on a device or against live Reverb or Firebase.
The SMS, payments and maps drivers have not been tried against live services.
The security headers have never been seen in a browser, and no performance measurements exist.
iOS has never been built, and the Xcode project was hand-edited.
The release agent also fixed a Slack alert setting that would have posted every log line.

When you're ready to run the checks, give me the command and I'll work through the failures.



