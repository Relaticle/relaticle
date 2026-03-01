# Workflows Block Library (Attio Reference)

Source: https://attio.com/help/reference/automations/workflows/workflows-block-library

## Trigger Blocks

"The starting point of any workflow — determine when a specific workflow should be initiated and what data will be passed to subsequent blocks."

### Record Triggers
- **Record command** — Manual button to trigger workflows
- **Record created** — Activates when new records are created
- **Record updated** — Fires when record attributes change
- **Attribute updated** — Triggers on specific attribute modifications

### List Triggers
- **List entry command** — Manual trigger for list entries
- **List entry updated** — Activates when list entries are modified
- **Record added to list** — Triggers when records join lists

### Data & Utility Triggers
- **Task created** — Initiates on task creation
- **Manual run** — On-demand workflow trigger
- **Recurring schedule** — Time-based automation
- **Webhook received** — External system integration trigger

## Action Blocks

### Records Actions
- Create or update record
- Create record
- Find records
- Update record

### Lists Actions
- Add record to list
- Delete list entry
- Find list entries
- Update list entry

### Tasks Actions
- Complete task
- Create task

### Calculations Actions
- Adjust time
- Aggregate values
- Formula
- Random number

### Conditions & Delays
- **Filter** — Continue only if conditions are met
- **If/else** — Branch based on conditions
- **Switch** — Multi-way branching
- **Delay** — Wait for a specified duration
- **Delay until** — Wait until a specific time

### AI & Agents
- Classify record/text
- Prompt completion
- Summarize record
- Research record

### Workspace Actions
- Broadcast message
- Celebration
- Round robin

### Utilities
- **Loop** — Iterate over a list of items
- **Parse JSON** — Extract data from JSON payloads
- **Send HTTP request** — Make external API calls

### Third-Party Integrations
- Slack, Outreach, Mailchimp, Mixmax, Typeform

## Images

- `images/aggregate-values.png` — Aggregate values block configuration
- `images/formula-block.png` — Formula block configuration
- `images/formula-result.png` — Formula block result
- `images/http-request-body.png` — HTTP request body configuration
