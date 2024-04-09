- [Requirements](#requirements)
- [Installing the VMChecker-Next Plugin](#installing-the-vmchecker-next-plugin)
- [Setting up the Middleware](#setting-up-the-middleware)
- [Enabling Debugging Capabilities](#enabling-debugging-capabilities)
- [Setting Up VS Code](#setting-up-vs-code)
- [Creating Pull Requests](#creating-pull-requests)
  - [Commits and commit history](#commits-and-commit-history)
- [Help](#help)

## Requirements
- You need to have a working Moodle installation. See the following comprehensive [tutorial](https://docs.moodle.org/403/en/Installing_Moodle) from the official Moodle docs!
- Ubuntu 20.04 or later
- Docker
- (Optional) [Vagrant](https://www.vagrantup.com/) - to install Moodle in a separate VM to not pollute your environment

## Installing the VMChecker-Next Plugin
- `git clone https://github.com/open-education-hub/vmchecker-next.git` - Clone VMChecker-Next
- Move the repository inside the `blocks` folder of the Moodle installation `mv vmchecker-next /var/www/html/moodle/blocks/vmchecker`
- To finish the installation go back to Moodle (`http://localhost:8080/moodle` - default), log in as the admin, and follow the onscreen instructions to finish the install

Reference: https://docs.moodle.org/403/en/Installing_plugins#Installing_manually_at_the_server

## Setting up the Middleware
To finish up the Moodle installation you will a need working `vmchecker-next-api` instance. You can get one up and running by using the following docker-compose file](https://github.com/open-education-hub/vmchecker-next-api/blob/master/etc/compose.yml)

See [TA Handbook](https://github.com/open-education-hub/vmchecker-next/wiki/Teaching-Assistant-Handbook) on how to set up a Moodle assignment as well as a Gitlab project (Note: You will only need to set up the private repository)

## Enabling Debugging Capabilities

Moodle offers debug capabilities. See the following [docs](https://docs.moodle.org/404/en/Debugging). For developers, you can check the [See also](https://docs.moodle.org/404/en/Debugging#See_also) section

**TLDR**; You will need to install XDebug. See the installation [instructions](https://xdebug.org/docs/install#linux).

## Setting Up VS Code

Moodle integrates with a few IDEs:
- VS Code: https://docs.moodle.org/dev/Setting_up_VSCode
- PHP Storm: https://docs.moodle.org/dev/Setting_up_PhpStorm
- NetBeans: https://docs.moodle.org/dev/Setting_up_Netbeans

## Creating Pull Requests

Please create a GitHub Issue that includes context for issues that you see. You can skip this if the proposed fix is minor.

In your pull requests (PR), link to the issue that the PR solves.

Please ensure that the base of your PR is the master branch.

### Commits and commit history

We prefer a clean commit history. This means you should squash all fixups and fixup-type commits before asking for a review (e.g., clean up, squash, then force push). If you need help with this, feel free to leave a comment in your PR, and we'll guide you.

## Help
If you get stuck or need help, you can always start a new GitHub Discussion.
