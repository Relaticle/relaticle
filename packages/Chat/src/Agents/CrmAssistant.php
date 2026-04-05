<?php

declare(strict_types=1);

namespace Relaticle\Chat\Agents;

use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Relaticle\Chat\Tools\Company\CreateCompanyTool as ChatCreateCompanyTool;
use Relaticle\Chat\Tools\Company\DeleteCompanyTool as ChatDeleteCompanyTool;
use Relaticle\Chat\Tools\Company\GetCompanyTool as ChatGetCompanyTool;
use Relaticle\Chat\Tools\Company\ListCompaniesTool as ChatListCompaniesTool;
use Relaticle\Chat\Tools\Company\UpdateCompanyTool as ChatUpdateCompanyTool;
use Relaticle\Chat\Tools\GetCrmSummaryTool;
use Relaticle\Chat\Tools\Note\CreateNoteTool as ChatCreateNoteTool;
use Relaticle\Chat\Tools\Note\DeleteNoteTool as ChatDeleteNoteTool;
use Relaticle\Chat\Tools\Note\GetNoteTool as ChatGetNoteTool;
use Relaticle\Chat\Tools\Note\ListNotesTool as ChatListNotesTool;
use Relaticle\Chat\Tools\Note\UpdateNoteTool as ChatUpdateNoteTool;
use Relaticle\Chat\Tools\Opportunity\CreateOpportunityTool as ChatCreateOpportunityTool;
use Relaticle\Chat\Tools\Opportunity\DeleteOpportunityTool as ChatDeleteOpportunityTool;
use Relaticle\Chat\Tools\Opportunity\GetOpportunityTool as ChatGetOpportunityTool;
use Relaticle\Chat\Tools\Opportunity\ListOpportunitiesTool as ChatListOpportunitiesTool;
use Relaticle\Chat\Tools\Opportunity\UpdateOpportunityTool as ChatUpdateOpportunityTool;
use Relaticle\Chat\Tools\People\CreatePersonTool;
use Relaticle\Chat\Tools\People\DeletePersonTool;
use Relaticle\Chat\Tools\People\GetPersonTool;
use Relaticle\Chat\Tools\People\ListPeopleTool as ChatListPeopleTool;
use Relaticle\Chat\Tools\People\UpdatePersonTool;
use Relaticle\Chat\Tools\SearchCrmTool;
use Relaticle\Chat\Tools\Task\CreateTaskTool as ChatCreateTaskTool;
use Relaticle\Chat\Tools\Task\DeleteTaskTool as ChatDeleteTaskTool;
use Relaticle\Chat\Tools\Task\GetTaskTool as ChatGetTaskTool;
use Relaticle\Chat\Tools\Task\ListTasksTool as ChatListTasksTool;
use Relaticle\Chat\Tools\Task\UpdateTaskTool as ChatUpdateTaskTool;

#[Provider(['anthropic', 'openai'])]
#[MaxSteps(15)]
#[Temperature(0.3)]
#[Timeout(120)]
final class CrmAssistant implements Agent, Conversational, HasMiddleware, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function instructions(): string
    {
        return <<<'PROMPT'
You are the Relaticle CRM Assistant, a helpful AI that helps users manage their CRM data.

## Capabilities
You can read and search all CRM data (companies, people, opportunities, tasks, notes).
You can propose creating, updating, or deleting CRM records -- but these require user approval.

## Rules
1. When a user asks to create, update, or delete a record, use the appropriate write tool. The tool will return a proposal that the user must approve or reject. Acknowledge the proposal naturally: "I've proposed [action]. Please review and approve above."
2. When a user asks to find, list, show, or search records, use the appropriate read tool and present results clearly.
3. For lists, present results in a compact table format. For single records, show key fields clearly.
4. Never fabricate data. If a search returns no results, say so.
5. Use entity names the user would recognize: "companies" not "organizations", "people" or "contacts" interchangeably, "opportunities" or "deals" interchangeably, "tasks", "notes".
6. When showing records, always include the record ID so the user can reference it.
7. If the user's request is ambiguous, ask for clarification rather than guessing.
8. Be concise. Don't over-explain CRM concepts the user likely knows.

## Write Operation Protocol
For any create, update, or delete operation:
- Use the appropriate write tool (e.g., CreateCompanyTool, UpdatePersonTool, DeleteTaskTool)
- The tool returns a pending_action proposal -- do NOT tell the user the action was completed
- Tell the user you've proposed the action and ask them to review the proposal card above
- Wait for the user to approve or reject before proceeding

## Formatting
- Use markdown for rich text formatting
- Use tables for list results
- Keep responses focused and actionable
PROMPT;
    }

    /**
     * @return list<Tool>
     */
    public function tools(): array
    {
        /** @var list<class-string<Tool>> $toolClasses */
        $toolClasses = [
            // Read tools
            ChatListCompaniesTool::class,
            ChatGetCompanyTool::class,
            ChatListPeopleTool::class,
            GetPersonTool::class,
            ChatListOpportunitiesTool::class,
            ChatGetOpportunityTool::class,
            ChatListTasksTool::class,
            ChatGetTaskTool::class,
            ChatListNotesTool::class,
            ChatGetNoteTool::class,
            SearchCrmTool::class,
            GetCrmSummaryTool::class,

            // Write tools
            ChatCreateCompanyTool::class,
            ChatUpdateCompanyTool::class,
            ChatDeleteCompanyTool::class,
            CreatePersonTool::class,
            UpdatePersonTool::class,
            DeletePersonTool::class,
            ChatCreateOpportunityTool::class,
            ChatUpdateOpportunityTool::class,
            ChatDeleteOpportunityTool::class,
            ChatCreateTaskTool::class,
            ChatUpdateTaskTool::class,
            ChatDeleteTaskTool::class,
            ChatCreateNoteTool::class,
            ChatUpdateNoteTool::class,
            ChatDeleteNoteTool::class,
        ];

        return array_map(
            static fn (string $class): Tool => resolve($class),
            $toolClasses,
        );
    }

    /**
     * @return array<int, class-string>
     */
    public function middleware(): array
    {
        return [];
    }
}
