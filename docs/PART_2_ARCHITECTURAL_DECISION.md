# Architectural Decision: Scaling Reads Under Heavy Writes

## Context

WINCH faces a dual pressure on the `orders` table: millions of rows inserted in burst windows during peak hours, while the dispatcher's active-orders screen has degraded past 5 seconds. Both pressures hit the same table, and any naive fix risks making the other worse, adding indexes accelerates reads but slows the already-strained writes.

## Measure first

Before any structural change, I would profile. Run `EXPLAIN` on the dispatcher's slow query, inspect the slow-query log over a real peak window, and check `SHOW ENGINE INNODB STATUS` for lock waits and buffer-pool churn. Without that data, every fix is a guess that may worsen the system it was meant to repair. Structural changes are expensive to ship and even more expensive to undo.

## Option A : Add an appropriate index

**Fits when** the dashboard's slowness traces to a full table scan. A composite index on `(status, created_at)`, or better a **partial index** on `status IN ('pending','assigned')`, lets MySQL skip the vast majority of rows when the dispatcher filters by active states.

**What it costs.** Every additional index adds B-tree maintenance on every `INSERT` and `UPDATE`. In a write-heavy workload, indexes compound the existing pressure. A partial index minimizes that, it only touches rows in the small active subset.

This is the cheapest, lowest-risk first move. Often it is enough.

## Option B : Redis cache

**Fits when** read pressure dominates *and* the data tolerates seconds of staleness.

**What it doesn't fix.** Write pressure. Caching a slow source leaves the source slow. It also introduces invalidation complexity (when does the cache expire, how do replicas stay consistent), operational burden (HA, failover, eviction tuning), and new failure modes (cache stampedes during invalidation). Adopting Redis before solving the write side treats a symptom while leaving the cause intact, exactly the trap the brief warns against.

## Option C : Separate active-orders table

**Fits when** the active set is a tiny fraction of total rows and both pressures need relief simultaneously.

A second table holding only `pending` and `assigned` rows means writes hit a small table (fast inserts, light index maintenance), and dispatcher reads scan thousands of rows instead of millions.

**What it costs.** Synchronization. Status transitions must update both tables atomically, enforced through transactions and Eloquent Observers. Risk of dual-write drift exists but is mitigated by transactional boundaries and a nightly reconciliation job.

## What I would do

1. **Measure first.** `EXPLAIN`, slow log, profile the actual bottleneck.
2. **Index second.** A partial index on the active subset, lowest risk, may suffice on its own.
3. **Split table third.** If writes degrade or reads remain slow, introduce the separate active-orders table. Solves both pressures structurally.
4. **Redis last.** Only after the structural problem is solved, as a read-through layer over the small active table, not as a band-aid over an unindexed million-row scan.

Reversing this order treats symptoms while leaving causes intact.
