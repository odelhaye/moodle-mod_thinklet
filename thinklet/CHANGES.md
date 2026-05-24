
## 0.9.5 beta

- Allows anonymous/guest users to view Thinklet activities when the course itself allows guest access.
- Adds an upgrade step so existing installations receive the updated guest permission automatically.

# Changelog

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