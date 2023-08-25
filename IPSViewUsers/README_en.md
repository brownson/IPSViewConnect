# IPSViewUsers Module for IP Symcon

The module allows assigning users to views with their own password

### Table of Contents

1. [Features](#1-features)
2. [Requirements](#2-requirements)
3. [Software Installation](#3-software-installation)
4. [Setting up the instances in IP-Symcon](#4-setting-up-the-instances-in-ip-symcon)
5. [State Variables and Profiles](#5-state-variables-and-profiles)
6. [PHP Command Reference](#6-php-command-reference)

### 1. Scope of functions

* IPSViewUsers

### 2. Requirements

- IP-Symcon from version 6.3

### 3. Software Installation

* Install the IPSViewConnect module via the Module Store.

### 4. Setting up the instances in IP-Symcon

- Under "Add Instance" the 'IPSViewUsers' module can be found using the quick filter.
     - More information on adding instances in the [instance documentation](https://www.symcon.de/en/service/documentation/basics/instances/)

__Configuration Page__:

name | Description
----------------------------- | ---------------------------------
list of groups                | Management of the groups
List of Users                 | Management of users


### 5. State Variables and Profiles

The status variables/categories are created automatically. Deleting individual ones can lead to malfunctions.

##### Status Variables

No status variables are created

##### Profiles:

No additional profiles will be added

### 6. PHP Command Reference

name | Description
------------------------------- | ---------------------------------
IVU_AddGroup                    | Adding a new group of users
IVU_ChangeGroup                 | Modifying an existing group
IVU_DeleteGroup                 | Delete a group

IVU_AddUser                     | Adding a new user
IVU_GetUserExists               | Checks if a user already exists
IVU_SetUserGroup                | Set a group of a user
IVU_SetUserPwd                  | Setting a user's password
IVU_SetUserView                 | Set a user's view
IVU_GetUserPwd                  | Returns the password of the given user
IVU_GetUserView                 | Returns the final view of a user
IVU_GetUserViewContent          | Delivers a user's final view as media content
IVU_GetUserViewID               | Returns the ViewID of the given user