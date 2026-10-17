# File25 Reviews 1435–1474

Forty static source checks: 37 witnesses and three defective preview-link callsites (lines 84, 94, 174 of templates/public-profile.php). One root cause: an undefined class name. All three callsites now reference the existing namespaced Public_URL policy. Regression test added. Main and deployed code remain separate; staging/live unverified.
