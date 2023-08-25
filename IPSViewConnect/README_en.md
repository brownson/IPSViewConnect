# IPSViewConnect Module for IP Symcon

The module provides an interface for IPSView clients.

### Table of Contents

1. [Features](#1-features)
2. [Requirements](#2-requirements)
3. [Software Installation](#3-software-installation)
4. [Setting up the instances in IP-Symcon](#4-setting-up-the-instances-in-ip-symcon)
5. [State Variables and Profiles](#5-state-variables-and-profiles)
6. [PHP Command Reference](#6-php-command-reference)

### 1. Scope of functions

* Authentication for IPSView clients
* Optimized data transfer for IPSView clients

### 2. Requirements

- IP-Symcon from version 5.4
- IPSViewDesigner from version 6.0

### 3. Software Installation

* Install the ViewConnect module via the Module Store.

### 4. Setting up the instances in IP-Symcon

- Under "Add Instance" the 'IPSViewConnect' module can be found using the quick filter.
     - More information on adding instances in the [instance documentation](https://www.symcon.de/en/service/documentation/basics/instances/)

__Configuration Page__:

name | Description
----------------------------- | ---------------------------------
Cached Views                  | List of all views that have already been loaded via the module.
Reset cache                   | Clearing the cache

### 5. State Variables and Profiles

The status variables/categories are created automatically. Deleting individual ones can lead to malfunctions.

##### Status Variables

No status variables are created

##### Profiles:

No additional profiles will be added

### 6. PHP Command Reference