# A framework you can understand and change

Stage exists so one person, with or without coding agents, can build and
maintain a PHP application. The first useful result should require few steps.
You should be able to follow a request from its route to the operation that handles it.

## Start with a working application

Choose a real caller and one complete workflow. Compose ordinary PHP objects.
Keep related features in one application until a measured constraint requires
a different deployment. An internal method call does not need a network.

## Use PHP and Composer conventions

Use Composer for packages, PHP types for inputs, constructors for dependencies,
and executable checks for behavior. Start with these conventions. Change one
when your application gives you a concrete reason.

## Give each rule one owner

Validate external input at its boundary. Put permissions and state changes in
the operation itself. A web page, command, or agent tool can then call that
operation without creating another version of its rules.

## Let people and agents inspect the same system

Use meaningful names, small feature directories, and documented commands.
Keep examples executable. An agent contribution must pass the same checks as
a human contribution. Agent output never proves identity or grants permission.

## Adapt the source

The source is MIT licensed. Read it, adapt it, and contribute what you learn.
Add an abstraction only when it removes work for a real caller. Keep stored
data and published interfaces in mind when changing that abstraction.

## Prove what you claim

Test the denied call and the stale write alongside the successful workflow.
Measure a bottleneck before optimizing it. Document the limits of each release.
Describe what failed and how the caller can recover.
