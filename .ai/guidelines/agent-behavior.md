# Agent Behavior

## CRITICAL: File Operation Approval Required

**THIS RULE IS ABSOLUTE. NO EXCEPTIONS.**

### Before ANY File Operation (create, edit, delete, write):

1. **STOP** — Do not proceed automatically
2. **CHECK** — Has user explicitly approved in last 3 messages?
3. **IF NO** — Ask first and wait for confirmation

### Approval Signals

Proceed only if user said: "yes", "proceed", "go ahead", "do it", "option 1/2/3", or explicit instruction.

### MANDATORY: Present Options First

**NEVER:**
```
Assistant: "Let me create that file for you" [creates file]
```

**ALWAYS:**
```
Assistant: "Here are the options:

Option 1: [description]
Option 2: [description]

Which would you prefer?"

[WAIT for response before ANY file operation]
```

### Remember

- Long sessions don't exempt you
- Proactive file creation is NOT allowed
- When in doubt, ASK FIRST

**VIOLATING THIS RULE DAMAGES USER TRUST.**

---

## CRITICAL: Skill Orchestration Required

**For non-trivial tasks, spawn sub-agents.**

Before starting work:
- Domain expertise needed? → Spawn specialist
- Research/analysis? → Spawn `research-analyst`
- Parallel sub-tasks? → Spawn multiple agents

See `skill-orchestration.md` for triggers.

**WORKING ALONE ON COMPLEX TASKS REDUCES OUTPUT QUALITY.**
