# Skill Orchestration

## CRITICAL: Sub-Agent Spawning Required

**THIS RULE IS MANDATORY FOR NON-TRIVIAL TASKS.**

### Pre-Task Checklist

**STOP. Before starting ANY task, verify:**

- [ ] Does this require domain expertise? → **MUST spawn specialist**
- [ ] Does this involve research/analysis/evaluation? → **MUST spawn `research-analyst`**
- [ ] Are there multiple independent sub-tasks? → **MUST spawn agents in parallel**
- [ ] Does user mention: security, test, review, refactor, debug, optimize? → **MUST spawn matching skill**

### If ANY Box is Checked

You MUST spawn sub-agents BEFORE attempting the task yourself.

**NEVER** do this:
```
User: "Evaluate these files for redundancy"
Assistant: [reads files and analyzes alone]
```

**ALWAYS** do this:
```
User: "Evaluate these files for redundancy"
Assistant: "I'll spawn research-analyst agents to analyze these in parallel."
[spawns Task sub-agents]
```

**FAILING TO SPAWN WHEN REQUIRED REDUCES OUTPUT QUALITY. ALWAYS LEVERAGE EXPERTISE.**

---

## Core Principle

**Research in parallel, act with approval.** Sub-agents investigate and recommend. File operations still require user approval per `agent-behavior.md`.

---

## Quick Trigger Reference

| If User Says... | Spawn |
|-----------------|-------|
| review, PR, check this | `code-reviewer` |
| test, coverage, edge case | `pest-testing` |
| security, vulnerabilities | `security-auditor` |
| refactor, clean up, simplify | `refactoring-specialist` |
| bug, error, not working | `debugger` |
| migration, schema, database | `database-administrator` |
| evaluate, compare, analyze, research | `research-analyst` |
| find, locate, search | `search-specialist` |
| architecture, design, scalability | `architect-reviewer` |
| payments, transactions, financial | `fintech-engineer` |

---

## When to Spawn Sub-Agents

Spawn when ANY of these conditions apply:

| Condition | Example |
|-----------|---------|
| Task requires deep domain expertise | Database schema changes → `database-administrator` |
| Task spans multiple domains | Payment refactor → `fintech-engineer` + `refactoring-specialist` |
| User mentions a domain keyword | "security", "test", "performance", "review" |
| Task involves risk | Production data, authentication, financial logic |
| Analysis would benefit from focused isolation | Code review, architecture assessment |
| Meta-discussion requires research | Evaluating options → `research-analyst` |
| Information retrieval needed | Finding patterns → `search-specialist` |

### Do NOT Spawn When

- Task is trivial (single-line fix, typo correction)
- Information is already in context
- User explicitly wants inline handling
- Sub-agent would just re-read files already in conversation

---

## Spawning Pattern

Use the Task tool:

```
Task(
  subagent_type: "general-purpose",
  prompt: """
  You are operating as the {skill-name} specialist.

  {Insert full skill content from .ai/skills/{skill-name}/SKILL.md}

  ## Your Task
  {Specific task description with file paths and context}

  ## Expected Output
  {What findings/recommendations to return}

  ## Constraints
  - Research only, do not modify files
  - Return actionable recommendations
  - Flag any concerns or risks
  """
)
```

### Parallel vs Sequential

| Scenario | Approach |
|----------|----------|
| Independent analyses | Parallel — spawn simultaneously |
| Dependent workflow | Sequential — wait for prior results |
| Research for decision-making | Parallel — gather options quickly |

---

## Result Synthesis

After sub-agents complete:

1. **Consolidate** — Merge findings into unified summary
2. **Reconcile** — Resolve conflicts (prefer safety)
3. **Prioritize** — Order by severity/impact
4. **Present** — Show user clear options with trade-offs
5. **Wait** — Do not proceed with file changes until approved

---

## Transparency

Always inform the user when spawning:

> "I'll spawn the `code-reviewer` and `security-auditor` agents to analyze this in parallel."

After completion:

> "The code-reviewer found 3 issues. The security-auditor flagged 1 concern. Here's the consolidated analysis..."
