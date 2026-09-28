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

The [feature example](features.md) shows a direct PHP call with permissions.
The [HTTP reference](http.md) shows how external requests reach those calls.
Stage does not require a model vendor, subscription, or autonomous runtime.

When exposing an operation to a remote agent, authenticate its credential on
the server. Grant only the permissions needed for its task. Apply resource
authorization inside the operation. A tool description, prompt, or caller-
supplied permission list is not evidence of authority.
