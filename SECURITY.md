# Security Policy

WP Ghost (formerly Hide My WP Ghost) is a security product, so we treat reports about
our own code with the seriousness we ask of everyone else. This document is the
coordinated vulnerability disclosure policy for the plugin, in both the free and the
premium edition.

Published by MINBO QRE SRL.

## Reporting a vulnerability

**Preferred route — Patchstack managed VDP.** WP Ghost participates in Patchstack's
managed Vulnerability Disclosure Program, which handles triage and researcher
coordination:

- Submit a report: <https://patchstack.com/database/report>
- Programme directory: <https://patchstack.com/database/vdp>

**Direct route — email.** If you would rather contact us directly, or your report is
time critical:

- security@hidemywpghost.com

Please do **not** open a public GitHub issue, a wordpress.org support topic, or a social
media post for an unfixed vulnerability. Those channels are read by people who are not
on our security team, and disclosure there puts every WP Ghost user at risk before a fix
exists.

### What to include

The more of this you can give us, the faster we can confirm and fix:

- The affected version, and the edition (free or premium)
- The vulnerability class, and the component or file involved
- Reproduction steps, ideally with a proof of concept
- The privilege level required (unauthenticated, subscriber, admin, and so on)
- What an attacker gains
- Your server environment, if it is relevant — server type, PHP version, multisite

## What you can expect from us

| Stage | Our commitment |
|---|---|
| Acknowledgement | Within **48 hours** (2 business days) of receiving your report |
| Triage and initial assessment | Within **7 days**, including whether we can reproduce it |
| Fix and release | As fast as severity warrants, up to **90 days** |
| Coordinated disclosure | At release, or **90 days** after the report, whichever is sooner |

We will keep you updated as the fix progresses rather than going quiet, and we will
credit you in the release notes unless you ask us not to.

If we need longer than 90 days for something genuinely complex, we will say so and agree
a revised date with you rather than let the deadline pass in silence.

## Safe harbour

We will not pursue legal action against you, or ask anyone else to, for security research
conducted in good faith under this policy. Good faith means:

- Testing only against websites you own or have written permission to test
- Not accessing, modifying, or destroying data belonging to anyone else
- Not degrading service for other users — no denial of service, no automated scanning
  that generates disruptive load
- Giving us a reasonable opportunity to fix the issue before disclosing it publicly

If you are unsure whether something is in scope, ask first.

## Scope

**In scope:** vulnerabilities in WP Ghost plugin code, in either edition — the paths and
rewrite layer, firewall rules, brute force protection, two-factor authentication,
temporary and magic logins, the logs, the admin interface, and the plugin's own update
mechanism.

**Out of scope:**

- Vulnerabilities in WordPress core, in a hosting environment, or in another plugin or
  theme, unless WP Ghost is what makes them exploitable
- Findings that require an administrator account to exploit, where an administrator
  could already achieve the same result through normal WordPress functionality
- Missing hardening headers or configuration recommendations with no demonstrated impact
- Reports produced entirely by an automated scanner with no verified exploitability
- Social engineering of our staff or customers

Third-party components bundled with the plugin are listed in `sbom.json`. Report those
upstream as well, but tell us too — we are responsible for what we ship.

## Support period

Security updates are provided for **5 years from the release of each version**.

**This version — WP Ghost 7.0.10, released September 2026 — is supported with security
updates until September 2031.**

The period runs from the version you are running, so installing a newer release moves
the end date forward. Staying on the current release line is therefore the reliable way
to stay covered, and each release states its own end date here.

## Actively exploited vulnerabilities

If you have evidence that a vulnerability in WP Ghost is being **exploited in the wild**,
say so explicitly and prominently in your report, and use the direct email route rather
than waiting on the queue. Active exploitation triggers regulatory notification
obligations on our side with a 24-hour clock attached, so that detail changes what we
have to do and how quickly.
