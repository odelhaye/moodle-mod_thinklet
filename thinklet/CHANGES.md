# Changelog

## 0.9.9 beta

- Presents a fresh continue/stop choice after every unsuccessful bonus series.
- Uses a neutral bonus-goal message until the learner is exactly one answer away.
- Corrects singular streak wording.

## 0.9.8 beta

- Always presents the continue/stop checkpoint before a reinforcement series ends.
- Shows the actual number of consecutive correct answers still required for the bonus.
- Reuses previous items until the chosen bonus challenge is completed.

## 0.9.7 beta

- Added a short-answer mode to reinforcement series.
- Shared the running score between consecutive reinforcement blocks.
- Added configurable block skipping when the learner chooses to stop.

## 0.9.6 beta

- Added a reinforcement MCQ block with a running score, streaks, delayed retry of missed items, an optional success sound, a bonus prompt, and explicit continue/stop controls.
- Added a repeatable item editor for reinforcement MCQ series.

## 0.9.5 beta

- Allows anonymous/guest users to view Thinklet activities when the course itself allows guest access.
- Adds an upgrade step so existing installations receive the updated guest permission automatically.

## 0.9.3 beta

- Restore: do not reinsert original IDs during course copy / restore.
- Safer restoration of Thinklet activities and blocks.

## 0.9.2 beta

- Declares Moodle 2 backup support via `FEATURE_BACKUP_MOODLE2`, so Thinklet activities are included in course duplication, backup and restore operations.


## 0.9 beta - 2026-05-24

- Renamed the plugin from MicroLab to Thinklet.
- Renamed Moodle component to `mod_thinklet`.
- Renamed database tables to `thinklet` and `thinklet_blocks`.
- Added `mod/thinklet:view` and `mod/thinklet:manageblocks` capabilities.
- Added Privacy API provider.
- Added basic backup and restore support.
- Added README and license notice.
- Set maturity to beta.
