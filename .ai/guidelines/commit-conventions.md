# Commit Conventions

## Format

[Conventional Commits](https://www.conventionalcommits.org) + [Gitmoji](https://gitmoji.dev) prefix.

```
<type>(<optional scope>): <gitmoji> <description>.

<optional body>

<optional footer(s)>
```

### Rules

- Description MUST begin with gitmoji + space
- Description MUST end with period
- ONE type and ONE description per commit
- Only include issue refs for REAL GitHub issues

### Example

```
feat(leads): ✨ Add email validation endpoint.

Fixes: #123
```

## Types

| Type | Description |
|------|-------------|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation only |
| `style` | Formatting (no code change) |
| `refactor` | Neither fix nor feature |
| `perf` | Performance improvement |
| `test` | Adding/correcting tests |
| `build` | Build system or dependencies |
| `ci` | CI configuration |
| `chore` | Other non-src/test changes |
| `revert` | Reverts previous commit |

## Scopes

Scopes only apply to `feat` and `fix` (customer-facing release notes).

**Use scope when:** Internal tooling or technical details customers wouldn't understand.

| Scope | Use For |
|-------|---------|
| `internal` | Internal tooling, technical details |
| `admin` | Admin-only features |

```
feat: ✨ Add password reset functionality.
fix: 🐛 Resolve checkout payment error.
feat(internal): ✨ Add admin debugging tools.
fix(internal): 🐛 Fix null check in PaymentProcessor.
```

## Gitmojis

| Emoji | Use Case |
|-------|----------|
| 🎨 | Improve structure / format of the code |
| ⚡️ | Improve performance |
| 🔥 | Remove code or files |
| 🐛 | Fix a bug |
| 🚑️ | Critical hotfix |
| ✨ | Introduce new features |
| 📝 | Add or update documentation |
| 🚀 | Deploy stuff |
| 💄 | Add or update the UI and style files |
| 🎉 | Begin a project |
| ✅ | Add, update, or pass tests |
| 🔒️ | Fix security or privacy issues |
| 🔐 | Add or update secrets |
| 🔖 | Release / Version tags |
| 🚨 | Fix compiler / linter warnings |
| 🚧 | Work in progress |
| 💚 | Fix CI Build |
| ⬇️ | Downgrade dependencies |
| ⬆️ | Upgrade dependencies |
| 📌 | Pin dependencies to specific versions |
| 👷 | Add or update CI build system |
| 📈 | Add or update analytics or track code |
| ♻️ | Refactor code |
| ➕ | Add a dependency |
| ➖ | Remove a dependency |
| 🔧 | Add or update configuration files |
| 🔨 | Add or update development scripts |
| 🌐 | Internationalization and localization |
| ✏️ | Fix typos |
| 💩 | Write bad code that needs to be improved |
| ⏪️ | Revert changes |
| 🔀 | Merge branches |
| 📦️ | Add or update compiled files or packages |
| 👽️ | Update code due to external API changes |
| 🚚 | Move or rename resources (e.g.: files, paths, routes) |
| 📄 | Add or update license |
| 💥 | Introduce breaking changes |
| 🍱 | Add or update assets |
| ♿️ | Improve accessibility |
| 💡 | Add or update comments in source code |
| 🍻 | Write code drunkenly |
| 💬 | Add or update text and literals |
| 🗃️ | Perform database related changes |
| 🔊 | Add or update logs |
| 🔇 | Remove logs |
| 👥 | Add or update contributor(s) |
| 🚸 | Improve user experience / usability |
| 🏗️ | Make architectural changes |
| 📱 | Work on responsive design |
| 🤡 | Mock things |
| 🥚 | Add or update an easter egg |
| 🙈 | Add or update a .gitignore file |
| 📸 | Add or update snapshots |
| ⚗️ | Perform experiments |
| 🔍️ | Improve SEO |
| 🏷️ | Add or update types |
| 🌱 | Add or update seed files |
| 🚩 | Add, update, or remove feature flags |
| 🥅 | Catch errors |
| 💫 | Add or update animations and transitions |
| 🗑️ | Deprecate code that needs cleaning |
| 🛂 | Work on authorization, roles and permissions |
| 🩹 | Simple fix for a non-critical issue |
| 🧐 | Data exploration/inspection |
| ⚰️ | Remove dead code |
| 🧪 | Add a failing test |
| 👔 | Add or update business logic |
| 🩺 | Add or update healthcheck |
| 🧱 | Infrastructure related changes |
| 🧑‍💻 | Improve developer experience |
| 💸 | Add sponsorships or money infrastructure |
| 🧵 | Add or update multithreading/concurrency code |
| 🦺 | Add or update validation code |
| ✈️ | Improve offline support |
| 🦖 | Add backwards compatibility code |

## Issue References

Only include for REAL issues being fixed. Each on its own line:

```
fix(auth): 🐛 Resolve token expiration bug.

Fixes: #789
Fixes: #790
```
