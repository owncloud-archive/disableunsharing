Disable Unsharing
=================

This app adds a hook to the unmount signal and throws an
exception if a normal user tries to unmount a share.

Admins and groups of admins
---------------------------

By default members of the 'admin' group can unshare shares.
To configure additional groups that can unshare shares you can
configure a comma separated list of groups:
```
  occ config:app:set disableunsharing admin-groups --value "admin,Admins - Region A, Admins - Region B"
```
