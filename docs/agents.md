# Work on a Stage application with an agent

Give the agent the application repository and one concrete user outcome.
Read its `AGENTS.md`, `composer.json`, and feature entry point together.
Locate the operation that owns the rule before changing HTTP or presentation.

1. Reproduce the requested behavior with a small input.
2. Identify its authorization and state boundaries.
3. Change the owning operation and its callers together.
4. Test successful, denied, and malformed calls.
5. Run the application's check command and inspect the actual result.
6. Report the changed behavior, checks, and remaining limits.

The [first HTTP application](getting-started.md) provides a runnable entry point
and response checks. [Compose features](features.md) builds operations with
permissions in the application's own namespace. Use [the HTTP reference](http.md)
to look up transport behavior. The framework's development tools and
`composer check` script are not inherited by applications through Composer.
Stage does not require a model vendor, subscription, or autonomous runtime.

When exposing an operation to a remote agent, authenticate its credential on
the server. Grant only the permissions needed for its task. Apply resource
authorization inside the operation. A tool description, prompt, or caller-
supplied permission list is not evidence of authority.
