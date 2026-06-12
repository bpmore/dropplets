# Spec: ActivityPub federation

Status: proposed (June 2026). Written per the roadmap's 4.1 decision point,
now that Phase 2 (tags, search, scheduling, feeds) has shipped. This is the
largest feature ever considered for Fieldnote; the estimate and a
recommendation are at the end.

## Problem

Readers increasingly live in the fediverse. A Fieldnote blog can be
followed only via RSS — invisible to someone whose reading happens in
Mastodon. ActivityPub federation makes every Fieldnote blog followable as
`@blog@yourdomain.com`: new posts arrive in followers' timelines, get
boosted, and link back to the real site. WriteFreely's headline feature,
on top of Fieldnote's better theming/accessibility/security story.

## Goals

- Followable from Mastodon (and compatible software) at `@<handle>@<domain>`
- Published posts federate as they go live — including scheduled ones
- Edits federate as Update; hide/delete federate as Delete
- Strictly opt-in (`federationEnabled`, default off; all endpoints 404 when off)
- No cron, no queue daemon — the lazy-work-on-request pattern that
  scheduled publishing proved
- The public reader surface stays zero-JS and unchanged

## Non-goals (v1)

- Receiving replies/comments, likes, or boosts into the site (the blog
  stays a publication, not a social inbox; Mastodon-side interactions live
  on Mastodon)
- Following other actors from the blog
- Multiple actors (single-admin model: one blog, one actor)
- Migrating followers between domains (Move activity) — documented caveat

## Design decisions

### Actor

One actor per blog. `type: Person` (a `Service` actor gets Mastodon's
"automated" badge; a hand-written blog is not a bot), `preferredUsername`
from a new config field `apHandle` (default `blog`), display name = site
name, summary = site info, icon = logo.svg rasterized? No — icon accepts
SVG poorly across implementations; use the configured OGImage when set,
else omit. Actor id: `https://<domain>/ap/actor`.

### Endpoints

```
GET  /.well-known/webfinger?resource=acct:<handle>@<host>   JRD
GET  /ap/actor          actor document (also serves the public key)
POST /ap/inbox          Follow / Undo(Follow) only; everything else 202-and-drop
GET  /ap/outbox         OrderedCollection of Create(Article), newest 20
GET  /ap/followers      collection (totalItems only — no member list exposed)
```

Post **object ids are the canonical post URLs**: the existing post route
content-negotiates — `Accept: application/activity+json` returns the AS2
Article JSON, browsers get HTML. Boosts therefore link to the real page,
and no parallel id space exists. (Webfinger and the AP endpoints bypass
the canonical-host redirect only in being defined after it — they're on
the canonical domain by construction.)

### Objects

`Article` (not `Note`): blogs have titles; Mastodon renders Articles as
title + summary + link, which is exactly right for long-form. Fields:
`name` (title), `summary` (fn_excerpt), `content` (Parsedown safe-mode
HTML, same as the feeds), `url`/`id` (canonical URL), `published`
(ISO 8601), `attributedTo` (actor), `tag` (Hashtag entries from post
tags), `attachment` (featured image as Image with absolutized URL).
Password-protected posts and drafts never federate — same rule as RSS.

### Keys and signatures

RSA-2048 keypair generated on enable into `data/activitypub/keys.json`
(private key never leaves the server; public key embedded in the actor).
Outbound requests sign with draft-cavage HTTP Signatures
(`(request-target) host date digest`) — this is the part of the protocol
with the worst interop folklore; budget for it.

Inbound: Follow/Undo must carry a valid signature from the claimed actor.
Verification requires fetching the remote actor's key — see SafeHttp.

### SafeHttp (prerequisite hoist)

Actor fetches and inbox deliveries POST/GET to arbitrary user-influenced
hosts — textbook SSRF surface. `ImageHandler` already solved this
(resolve host, reject private/reserved ranges, pin curl to the validated
IP, no redirects); hoist `safeResolvedTarget()` + the pinned-curl pattern
into `src/SafeHttp.php` and have both consumers use it. Remote actor keys
cached in `data/activitypub/actors/` with a TTL so each follower costs one
fetch, not one per verification.

### Followers and delivery

`data/activitypub/followers.json`: actor id, inbox, sharedInbox, addedAt.
Accept(Follow) is sent synchronously from the inbox handler (the remote
server is already talking to us). 

Post delivery uses a flat-file queue, `data/activitypub/queue/*.json`
(one file per inbox × activity, deduped by sharedInbox). Enqueued by:
publish route, the lazy scheduled publisher, first-publish-via-edit,
Update on edits of published posts, Delete on hide/delete. Drained
opportunistically: after a response finishes (`fastcgi_finish_request()`
when available, else inline cap of 2 items), each item gets a 5s-timeout
signed POST; failures reschedule with exponential backoff (1m, 15m, 2h,
1d), dropped after 7 days. A blog with 50 followers on 30 instances
drains a publish in a handful of page views — fine for the traffic class.

### Settings & UX

A "Federation" fieldset: enable toggle, handle field (locked once enabled
— changing it orphans followers), the resulting `@handle@domain` shown
copy-ready, follower count, and the caveats (HTTPS required; domain
changes orphan followers, same as passkeys). Dashboard shows follower
count next to the Views panel.

## Security notes

- All inbox payloads are size-capped (64 KB) and parsed defensively;
  unknown activity types are dropped with 202 (never an error oracle)
- Inbox shares the IP throttle pattern (its own bucket, not the login one)
- Signature verification failure → 401, no retry hint
- SafeHttp everywhere a remote host is contacted; no redirects followed
- Private key file 0640, outside web root, excluded from the 3.1 export

## The honest dragons

1. **HTTP Signature interop.** Mastodon, Pleroma/Akkoma, GoToSocial, and
   Misskey disagree on header sets, digest casing, and key formats in
   long-documented ways. The verify-against-real-instances loop is the
   bulk of the estimate.
2. **Authorized fetch.** Instances running secure mode sign their GETs and
   expect us to verify before serving actor/object JSON; v1 serves public
   documents unsigned-readable, which is compatible, but inbound signed
   fetches must not break.
3. **No push without traffic.** Lazy draining means a zero-traffic blog
   delivers on the next visit (often the author's own). Acceptable;
   document it. A `php bin/ap-deliver.php` CLI lets cron users opt into
   immediacy.
4. **Deliverability ≠ visibility.** Followers see posts; the blog won't
   appear in full-text search on most instances. Set expectations in docs.

## Acceptance criteria

1. Enable federation, search `@blog@domain` from a real Mastodon account,
   follow: the follow is accepted and the count increments
2. Publish a post (manual AND scheduled): it appears in the follower's
   home timeline as an Article linking to the canonical URL, with tags
   as hashtags and the featured image attached
3. Edit → the timeline entry updates; hide/delete → it disappears
   (Tombstone served at the object URL for AP requests)
4. Unfollow → follower removed; re-follow works
5. Protected posts and drafts never appear in outbox or deliveries
6. Disabling federation 404s every endpoint and stops deliveries;
   re-enabling keeps the keypair (followers survive a toggle)
7. Smoke: webfinger/actor/outbox JSON shapes, unsigned Follow → 401,
   oversized inbox payload → 413, endpoints 404 when disabled
8. Manual interop pass against mastodon.social + one GoToSocial instance

## Estimate and recommendation

| Phase | Scope | Size |
|---|---|---|
| AP-1 | SafeHttp hoist; keys; webfinger + actor + followers; signed inbox Follow/Undo + Accept | L — SHIPPED |
| AP-2 | Article objects + content negotiation on post URLs; outbox; queue + lazy delivery; Create/Update/Delete wiring | L |
| AP-3 | Settings UI, dashboard count, bin/ap-deliver.php, docs, interop hardening pass | M |

Total: roughly the effort of everything shipped on 2026-06-12 combined.
Recommendation: build AP-1 alone first and live with "followable but
silent" for a week against real instances — signature interop is proven
or disproven at minimum cost before AP-2's queue work begins. If interop
turns into a swamp, a fallback exists: drop v1 entirely and document
fediverse presence via the RSS bridge at fed.brid.gy, which requires zero
code. Worth building only if first-party `@blog@yourdomain` identity
matters to the project's story — I believe it does, but it is a
conviction call, not an obvious one.
