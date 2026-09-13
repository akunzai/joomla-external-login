# Issue tracker: GitHub

**This file is English throughout**, sample blocks included, so it reads
one way to every model, whatever language the repo chose for its issues.

Issues live as GitHub issues. Use the `gh` CLI for all operations; it infers
the repo when run inside a clone.

Write issue titles and descriptions in **English**.

## Conventions

- **Create**: `gh issue create --title "..." --body "..."`. Use a heredoc for multi-line bodies.
- **Read**: `gh issue view <number> --comments`
- **List**: `gh issue list --state open --json number,title,labels`
- **Comment**: `gh issue comment <number> --body "..."`
- **Label**: `gh issue edit <number> --add-label "..."`
- **Close**: `gh issue close <number>`

Use a concise descriptive title with no Conventional Commit prefix.

## Description shape

1. Open with what a product manager or a new engineer would observe: the
   symptom or the request, in plain language. Skip file paths and
   function names unless the reader cannot otherwise locate the issue.
2. Add a visual the forge renders inline — a screenshot or recording for
   a UI bug, a Mermaid diagram for a flow or state problem. Skip formats
   the description editor cannot render, such as a link to an external
   artifact or a raw HTML or SVG file. Upload it with the repeatable `--attach` flag
   (`gh issue create --attach './bug.png#The error state'`);
   alt text follows the path after `#`. Only when capture is genuinely
   impossible, leave `<!-- screenshot pending: <what it should show> -->`
   rather than omitting it silently.
3. Close with a collapsed technical section, so it does not push the
   human summary below the fold:

```markdown
<details>
<summary>Technical details</summary>

<everything an implementer needs — for example, suspected cause, related
code paths, repro commands, log excerpts>

</details>
```

**No personally identifiable information in any attachment**; use test
data, masking, or cropping.

## Spec issues

An issue an agent will implement from carries a different shape, because
its reader is building rather than triaging. Acceptance criteria stay
above the fold; only background goes into `<details>`.

```markdown
<one paragraph: the observable outcome, in English>

## Acceptance criteria

- [ ] <checkable statement about observable behaviour>
- [ ] <one per criterion; a reviewer can tick these without reading code>

## Scope

- In: <paths or areas>
- Out: <what this issue deliberately does not change>

## Verification

<how to prove it works, per docs/agents/verification.md; say here when
this needs a deployed environment rather than a local run>

<details>
<summary>Technical details</summary>

<only background — for example, related code paths, prior art, log
excerpts, open questions>

</details>
```

Use the vocabulary the project already defines for its domain, so the
issue, the tests, and the code name the same things.

An issue with unanswered open questions is not ready to implement. Say
so in the issue rather than letting an agent guess.

## Labels

This repo's own labels, read from `gh label list --limit 100`. Both CLIs
default to 30 and report that page as the whole set, so a label past the
first page reads as absent. Nothing here invents a vocabulary; when a
label really is missing, that is a conversation with the maintainer, not
a label to create.

Check the other documents under `docs/agents/` before listing. Where one
already owns part of this vocabulary — `triage-labels.md` owns the triage
roles — point at it and list only what it does not cover. A label named in
both places has two owners and one of them goes stale on the next rename.

- **Required on every issue**: none
- **Applied when it applies**:
  - Triage roles: see `triage-labels.md` (`ready-for-agent`, `wontfix`)
  - Issue categories: `bug`, `enhancement`, `documentation`, `question`
  - Community/triage: `help wanted`, `good first issue`, `duplicate`, `invalid`
  - Wayfinder: `wayfinder:map`, `wayfinder:research`, `wayfinder:grilling`, `wayfinder:prototype`, `wayfinder:task`

## Pull requests as a triage surface

**PRs as a request surface: no.** _(Set to `yes` if this repo treats external PRs as feature requests; `/triage` reads this flag.)_

When set to `yes`, PRs run through the same labels and states as issues, using the `gh pr` equivalents:

- **Read a PR**: `gh pr view <number> --comments` and `gh pr diff <number>` for the diff.
- **List external PRs for triage**: `gh pr list --state open --json number,title,body,labels,author,authorAssociation,comments` then keep only `authorAssociation` of `CONTRIBUTOR`, `FIRST_TIME_CONTRIBUTOR`, or `NONE` (drop `OWNER`/`MEMBER`/`COLLABORATOR`).
- **Comment / label / close**: `gh pr comment`, `gh pr edit --add-label`/`--remove-label`, `gh pr close`.

GitHub shares one number space across issues and PRs, so a bare `#42` may be either — resolve with `gh pr view 42` and fall back to `gh issue view 42`.

## When a skill says "publish to the issue tracker"

Create a GitHub issue.

## When a skill says "fetch the relevant ticket"

Run `gh issue view <number> --comments`.

## Wayfinding operations

Used by `/wayfinder`. The **map** is a single issue with **child** issues as tickets.

- **Map**: a single issue labelled `wayfinder:map`, holding the Notes / Decisions-so-far / Fog body. `gh issue create --label wayfinder:map`.
- **Child ticket**: an issue linked to the map as a GitHub sub-issue (`gh api` on the sub-issues endpoint). Where sub-issues aren't enabled, add the child to a task list in the map body and put `Part of #<map>` at the top of the child body. Labels: `wayfinder:<type>` (`research`/`prototype`/`grilling`/`task`). Once claimed, the ticket is assigned to the driving dev.
- **Blocking**: GitHub's **native issue dependencies** — the canonical, UI-visible representation. Add an edge with `gh api --method POST repos/<owner>/<repo>/issues/<child>/dependencies/blocked_by -F issue_id=<blocker-db-id>`, where `<blocker-db-id>` is the blocker's numeric **database id** (`gh api repos/<owner>/<repo>/issues/<n> --jq .id`, _not_ the `#number` or `node_id`). GitHub reports `issue_dependencies_summary.blocked_by` (open blockers only — the live gate). Where dependencies aren't available, fall back to a `Blocked by: #<n>, #<n>` line at the top of the child body. A ticket is unblocked when every blocker is closed.
- **Frontier query**: list the map's open children (`gh issue list --state open`, scoped to the map's sub-issues / task list), drop any with an open blocker (`issue_dependencies_summary.blocked_by > 0`, or an open issue in the `Blocked by` line) or an assignee; first in map order wins.
- **Claim**: `gh issue edit <n> --add-assignee @me` — the session's first write.
- **Resolve**: `gh issue comment <n> --body "<answer>"`, then `gh issue close <n>`, then append a context pointer (gist + link) to the map's Decisions-so-far.
