# File25 R1115–1154 — HTML profile search audit

Source freeze: main `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`, stacked parent `263fd0387dae7cfae936367f38d499848b32a64b`.

Forty distinct targeted source checks performed before correction: 36 source witnesses, four deficient search scopes, one shared root cause. `Profile_Renderer::prepare_response()` sets `search_error` for malformed, oversized, bidi-control or disabled profile-local search but still calls `Timeline_Service::get_for_author()` with an empty search, expanding the result to the unfiltered public timeline. REST instead rejects invalid search. This is a source-level defect, not a confirmed deployed incident.

The proposed fix is to skip Timeline retrieval when `search_error` is nonempty, leaving the existing empty result and error presentation. Code correction, behavioral regression and exact-head CI are pending. The complete File25 and Three Central original DOCX plans were not fully reread; cross-file native owner contracts remain subject to owner acceptance.

No merge or deployment. Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
