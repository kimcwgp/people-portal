# People Portal documentation

Module guides and release notes, one PDF each.

## Module guides

| Guide | Covers |
|---|---|
| `Profile-Guide.pdf` | Profile — what the page shows, where each field comes from, what an associate may change |
| `Dashboard-Guide.pdf` | Dashboard — the time clock, breaks, and the approval inbox |
| `My-Records-Guide.pdf` | Attendance, Time Entries, Leave Requests, My Shift |
| `Time-Entries-Guide.pdf` | Time Entries in depth — the grid, the lifecycle, the supervisor view |
| `My-Team-Guide.pdf` | Team's Attendance, Time Entries, Leaves and Shift |
| `HR-Modules-Guide.pdf` | Users, roles, announcements, credits, regularization, holidays, logs, proxy screens |
| `Settings-Guide.pdf` | Projects, Clients, Leave Types, Shifts |

## Release notes

| Document | Covers |
|---|---|
| `Change-Log.pdf` | Everything changed in the current round of work, plus known issues still open |

## Editing a guide

These are PDFs only. Each was rendered from a self-contained HTML source that
is no longer kept in the tree, but is still in git history:

```bash
git show 7661725:docs/Profile-Guide.source.html > Profile-Guide.source.html
```

Edit that file, then print it to PDF from a browser at Letter size with
0.55in top / 0.6in bottom / 0.7in side margins to match the existing layout.

Nothing here is served to the web — `docs/` sits outside `public/`.
