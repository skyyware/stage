# Compose features

Keep a feature's input types, operations, and private implementation together.
The executable example under `examples/Features` has a counter and a report.

```php
$counter = new Example\Counter\Counter();
$caller = new Stage\Security\Caller('user-123', ['counter.read', 'counter.write']);
$counter->increment(new Example\Counter\Increment(2), $caller);
$report = new Example\Reports\Report($counter);
$total = $report->total($caller);
```

The trusted authentication adapter supplies the caller. A caller is a value,
not proof that authentication happened. Never construct its permissions from
an untrusted request. Permissions here are exact strings, without wildcards.
Applications remain responsible for resource and tenant authorization.

`Counter` rejects invalid input through `Increment` and checks permissions
inside both operations. Calling the object from a command or an agent adapter
therefore follows the same rule. An HTTP adapter translates `Forbidden` into
its chosen 403 response. The security value has no HTTP dependency.

`Report` depends on `Counter/Contract/ReadCount`, which exposes only the read
operation. `deptrac.yaml` rejects a dependency on `Counter` itself. Copy this
rule for a real application's feature names and exercise a forbidden import.
Static dependency checks do not prevent shared-database access or dynamic
reflection. Test those boundaries separately if your application uses them.

The example stores one count in memory. It does not implement transactions,
concurrency, or tenant storage. Create request-specific objects per request.
An immutable router does not make mutable objects captured by handlers safe
to share in a persistent worker.
