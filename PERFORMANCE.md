# File 25 Performance and Reliability Contract

## Scope

File 25 is a presentation/projection consumer. It must not create an unbounded analytics, search, publication, clinic or profile-master backend. Provider calls are bounded, paginated and fail closed; optional provider failure must not make the entire profile fatal.

## Source controls

Public REST and timeline/section reads use bounded limits, deterministic cache validators where permitted, no-store protection for sensitive/profile responses until a reviewed partition contract exists, safe same-site URLs, bounded public rate limiting, and defensive provider normalization. Derivative timeline indexes are rebuildable and never replace native source truth.

## External measurements still required

The exact staging artifact must be measured on representative low-end devices and slow networks, including p75/p95 latency, payload size, query/provider behavior, cache/CDN behavior, provider degradation and recovery. These measurements must be attached to the release evidence before production approval.

**Current status:** source controls present; real staging performance evidence pending.
