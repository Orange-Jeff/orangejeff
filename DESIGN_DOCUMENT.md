## Extension Goals and Scope

### Purpose of the Extension
The primary goal of this extension is to provide a secure and user-controlled mechanism for AI coding agents to access and utilize content from VS Code's local file history.

### Problem Solved
Currently, AI coding agents lack the ability to directly access VS Code's local file history. This limitation makes it difficult for users to instruct agents to recover from recent errors, refer to uncommitted changes, or analyze the evolution of a file without resorting to manual copy-pasting of historical content. This extension aims to bridge that gap, enabling more seamless and efficient collaboration between developers and AI agents.

### What the Extension Will Do (Scope - Inclusions)
*   **User-Initiated Sharing:** The extension will facilitate the sharing of specific historical file versions with an AI agent, initiated and approved by the user.
*   **Request Historical Versions List:** An AI agent will be able to request a list of available historical versions for a currently active file. The user will be prompted to confirm or select which versions (if any) are shared with the agent.
*   **Request Specific Historical Content:** An AI agent will be able to request the content of a specific historical version of a file, identified from the previously shared list. The user will again be prompted to confirm this action before the content is shared.
*   **Clear User Prompts and Confirmations:** Every instance of data sharing (listing versions or providing content) will require explicit user confirmation through clear and understandable prompts.

### What the Extension Will NOT Do (Scope - Exclusions)
*   **No Automatic Access:** The extension will not automatically access any file or file history without an explicit user action or confirmation for each specific instance.
*   **No Free Browsing of `.history`:** The AI agent will not be allowed to browse the entire `.history` folder or its contents freely. Access is strictly limited to specific versions of files explicitly approved by the user.
*   **No Automatic File Reversion:** The extension will not automatically revert files to a previous state. Any file modifications based on historical content will be performed by the AI agent using its standard file manipulation capabilities, after the user has received and potentially reviewed the historical content.
*   **No Credential Storage or Unnecessary Network Access:** The extension will not store any user credentials. Any potential network access required for communication with the AI agent will be clearly defined, secured, and minimized to only what is essential for the extension's functionality.
*   **Focus on Local History, Not Git:** This extension is specifically focused on VS Code's local, often ephemeral, file history. It will not interact with version control systems like Git.

### Target User
The target users are developers who use VS Code and frequently interact with AI coding assistants. These users are looking to enhance their workflow by enabling AI agents to securely access and leverage the context available in their local file history, thereby improving error recovery, change tracking, and overall coding efficiency.

## User Interaction (UX) within VS Code

### Activation/Entry Points
Users will primarily access the extension's features through:
*   **Command Palette:** Typing "History Helper" will reveal the available commands.
*   **Context Menus:** Right-clicking on an active editor tab or a file in the Explorer view will offer relevant "History Helper" actions.

### Key Commands (from Command Palette)

*   **`History Helper: Share historical version with AI Agent`**
    1.  The user invokes this command from the Command Palette or a context menu.
    2.  If no file is active, the user might be prompted to select an open file or a file from the workspace. If a file is active, it will be the default target.
    3.  The extension retrieves the local history for the target file from VS Code's built-in history provider.
    4.  A Quick Pick dropdown appears, listing available historical entries. Each entry will display a timestamp and potentially a brief label (e.g., "2 minutes ago", "Last saved by user").
    5.  The user selects a specific version from the list.
    6.  The extension fetches the content of the selected historical version.
    7.  A notification informs the user: "Historical content for [filename] ([timestamp]) is ready. Inform your AI agent it can request this content." (The exact mechanism for how the agent retrieves this will be defined by the agent's capabilities and the communication bridge, e.g., via a specific tool call like `get_shared_historical_content`).

*   **`History Helper: Respond to AI Agent's request for history`**
    1.  This command is primarily invoked internally when an AI agent, through its own tooling, sends a request to the extension (e.g., `request_historical_versions_list` or `request_specific_historical_content` - see Agent-Facing API section).
    2.  A modal notification appears: "AI Agent is requesting access to historical versions for [filename]. [Allow] [Deny]".
    3.  If the user clicks "Allow":
        *   If the agent requested a list: A Quick Pick shows available history entries for the specified file. The user selects one or more entries (or "all" recent entries, up to a reasonable limit) to make available to the agent. A notification confirms: "[N] historical versions of [filename] are now available for the AI agent to list."
        *   If the agent requested specific content (and the user previously allowed listing): A Quick Pick might ask the user to confirm sharing this specific version (if not previously confirmed) or directly a notification: "Historical content of [filename] from [timestamp] is now available for the AI Agent."
    4.  If the user clicks "Deny", a notification is shown: "Access to historical versions for [filename] was denied." The agent is informed that the request was denied.

### User Interface Elements
*   **Quick Pick:** Used for selecting files (if needed) and choosing specific historical versions from a list.
*   **Notifications:** VS Code's standard notification system (toasts, status bar messages, and modal dialogs for critical confirmations) will be used for prompts, confirmations, and error messages.
*   **No Custom Webviews:** To maintain simplicity and a native feel, custom webview UIs will be avoided.

### Workflow Examples

#### User-initiated sharing:
1.  **Activation:**
    *   User right-clicks on the editor tab for `script.py` -> selects "History Helper: Share historical version with AI Agent".
    *   *Alternatively:* User opens Command Palette (Ctrl+Shift+P) -> types "History Helper: Share historical version with AI Agent" and selects it.
2.  **History Selection:** A Quick Pick dropdown appears, listing entries like:
    *   `script.py (5 minutes ago)`
    *   `script.py (30 minutes ago - before refactor)`
    *   `script.py (2 hours ago)`
3.  User clicks on `script.py (5 minutes ago)`.
4.  **Confirmation:** A VS Code notification appears: "Selected historical content for `script.py` (5 minutes ago) is now available for the AI agent." The user would then instruct the agent (e.g., "I've shared a previous version of `script.py`, can you look at it using `get_shared_historical_content`?").

#### Agent-initiated request (user response):
1.  **Agent Request:** The AI Agent, through its chat interface or another mechanism, decides it needs to see the history of `utils.ts`. It uses a tool call like `request_historical_versions_list(filename="utils.ts")`.
2.  **User Prompt:** A modal notification appears in VS Code: "AI Agent requests access to local history for `utils.ts`. [Allow] [Deny]".
3.  **User Action:** User clicks "Allow".
4.  **History Selection (if listing versions):** A Quick Pick shows history entries for `utils.ts`. User selects a few versions they deem relevant or an "Allow all for this session" option.
5.  **Notification to User:** "Historical versions of `utils.ts` have been made available to the AI Agent."
6.  **Agent Follow-up:** The agent would then receive the list of version identifiers and could subsequently request specific content using `request_specific_historical_content(filename="utils.ts", version_id="...")`. This would again prompt the user for confirmation for that specific version if not covered by a broader "allow" permission.
7.  **Final Confirmation (for content):** After user confirms sharing a specific version, a notification: "Historical content of `utils.ts` (20 minutes ago) has been made available to the AI Agent."

## Agent-Facing API/Tool Interface

This section details the "tools" an AI agent would use to interact with the VS Code History Helper extension. These tools are.
Expected to be exposed to the agent via its existing tool-using framework.

### Tool 1: `history.requestVersions`

*   **Description:** Allows the agent to request a list of available historical versions for a specified file. The user will be prompted to approve this request and select the versions they want to make available.
*   **Inputs:**
    *   `filePath` (string, required): The workspace-relative path to the file (e.g., `src/components/myComponent.js`).
*   **Outputs (Return Values):**
    *   On success (user approves and selects versions):
        ```json
        {
          "status": "success",
          "versions": [
            { "id": "unique_version_id_1", "timestamp": "YYYY-MM-DDTHH:mm:ssZ", "label": "e.g., 5 minutes ago" },
            { "id": "unique_version_id_2", "timestamp": "YYYY-MM-DDTHH:mm:ssZ", "label": "e.g., 2 hours ago" }
          ]
        }
        ```
    *   If the user denies the request:
        ```json
        { "status": "denied_by_user" }
        ```
    *   If the file is not found, has no local history, or another error occurs:
        ```json
        { "status": "error", "message": "File not found, no history available, or other error." }
        ```
*   **Interaction Model:**
    1.  The agent calls `history.requestVersions` with a `filePath`.
    2.  The extension triggers a VS Code modal notification: "AI Agent is requesting access to historical versions for '[filePath]'. [Allow] [Deny]".
    3.  If the user clicks "Allow", the extension retrieves local history for the file.
    4.  A Quick Pick list appears, showing available historical entries (timestamps, labels). The user can select multiple entries.
    5.  The extension returns the list of `id`, `timestamp`, and `label` for only the versions the user selected.
    6.  If the user clicks "Deny", or closes the prompt, the "denied_by_user" status is returned.

### Tool 2: `history.getVersionContent`

*   **Description:** Allows the agent to request the actual content of a specific historical version of a file, identified by its `versionId` (obtained from `history.requestVersions`). The user will be prompted to approve this specific request.
*   **Inputs:**
    *   `filePath` (string, required): The workspace-relative path to the file.
    *   `versionId` (string, required): The unique identifier for the version (obtained from a previous `history.requestVersions` call).
*   **Outputs (Return Values):**
    *   On success (user approves sharing the specific version):
        ```json
        {
          "status": "success",
          "filePath": "path/to/file.ext",
          "versionId": "unique_version_id_1",
          "content": "The full text content of the historical file."
        }
        ```
    *   If the user denies the request:
        ```json
        { "status": "denied_by_user" }
        ```
    *   If the `versionId` is invalid, the file is not found, the history entry has expired, or another error:
        ```json
        { "status": "error", "message": "Version ID invalid, file not found, history entry expired, or other error." }
        ```
*   **Interaction Model:**
    1.  The agent calls `history.getVersionContent` with `filePath` and `versionId`.
    2.  The extension triggers a VS Code modal notification: "AI Agent is requesting to view the content of a historical version of '[filePath]' (Timestamp: [version_timestamp_or_label]). [Allow] [Deny]".
    3.  If the user clicks "Allow", the extension retrieves the content for that specific version and returns it.
    4.  If the user clicks "Deny", or closes the prompt, the "denied_by_user" status is returned.

### Tool 3: `history.getSharedContent` (User-initiated, Agent-consumed)

*   **Description:** This tool is called by the agent when the user has proactively shared a historical version via a VS Code command (e.g., "History Helper: Share historical version with AI Agent") and has notified the agent that content is ready. This tool allows the agent to retrieve that pre-shared content.
*   **Inputs:**
    *   `filePathHint` (string, optional): The path of the file the agent expects content for. This helps the extension provide the correct content if, in the future, multiple files could be shared simultaneously (though the initial design focuses on one at a time).
*   **Outputs (Return Values):**
    *   On success (content was shared by user and is available, and if `filePathHint` is provided, it matches):
        ```json
        {
          "status": "success",
          "filePath": "path/to/file.ext",
          "content": "The full text content of the historical file shared by the user."
        }
        ```
    *   If no content has been actively shared by the user via the VS Code UI command:
        ```json
        { "status": "no_content_available" }
        ```
    *   If `filePathHint` was provided but does not match the file path of the content shared by the user:
        ```json
        { "status": "no_matching_content", "message": "Content for a different file ([actual_shared_filepath]) was shared by the user, not for the hinted [filePathHint]." }
        ```
*   **Interaction Model:**
    1.  The user, through a VS Code command (e.g., "History Helper: Share historical version with AI Agent"), selects a file and a specific historical version.
    2.  The extension temporarily stores this selected content and notifies the user: "Historical content for [filename] ([timestamp]) is ready. Inform your AI agent it can request this content."
    3.  The agent, prompted by the user, calls `history.getSharedContent`.
    4.  The extension checks if there's user-shared content available.
        *   If yes, and `filePathHint` (if provided) matches, it returns the content.
        *   If no content is stored, it returns `no_content_available`.
        *   If content is available but for a different file than `filePathHint`, it returns `no_matching_content`.
    5.  This tool does *not* trigger any new user prompts in VS Code, as the sharing action was already authorized by the user through the UI. The extension should clear the temporarily stored content after it's successfully retrieved by the agent or after a certain timeout/event (e.g., new sharing action).

## Core Extension Logic (Conceptual)

### 1. Accessing Local File History

*   **Primary Method:** The extension will primarily attempt to use VS Code's existing commands and APIs to access local file history. Investigation is needed to determine the best approach. Potential avenues include:
    *   Executing VS Code commands like `workbench.action.localHistory.view` or `workbench.action.localHistory.compareWithFile` programmatically and then attempting to extract information from the resulting views or temporary files. This might be complex and fragile.
    *   Exploring if `vscode.workspace.fs` or other workspace APIs offer any direct, structured access to local history entries (URIs, timestamps). Ideally, an API would provide a list of historical versions for a given file URI.
    *   VS Code's built-in "Timeline" view might offer APIs that extensions can tap into to get a list of historical entries. This is a promising area to investigate.
*   **Alternative/Fallback (If direct API access is limited):**
    *   If a structured list cannot be obtained directly, the extension might guide the user. For instance, when `history.requestVersions` is called, if the extension cannot populate the list itself, it could prompt the user: "Please open the Local History / Timeline view for [filename], select a version, and use the 'History Helper: Share selected version from Timeline' command."
    *   The "Share selected version from Timeline" command would then attempt to grab the content of the version currently focused or selected in the native Timeline view or a diff editor. This is less ideal due to increased user steps and potential reliance on UI state. Copy-pasting by the user would be a last resort and is actively discouraged.
*   **Note on History Structure:** The extension will treat VS Code's local history store as an opaque, internal system. It will only read information (timestamps, content) and will not attempt to write to, modify, or manage the local history files directly. The ephemeral nature of this history (it can be cleared by VS Code or the user) will be considered in error handling.

### 2. Managing User Permissions and Choices

*   **Per-Request Confirmation:** The default and primary security model will be explicit, per-request confirmation from the user for any data sharing action (listing versions or providing content).
*   **No Long-Term Permission Storage:** Initially, the extension will not implement features like "Always allow for this file" or "Allow for the next X minutes." This simplifies the security model and ensures the user is always in control for each distinct request. Future enhancements could consider time-limited permissions if strongly desired, but this would require careful design.
*   **Temporary Storage of Selections:**
    *   When a user approves a `history.requestVersions` call and selects specific versions from the Quick Pick, the extension will temporarily store a mapping of the `versionId` (generated by the extension, e.g., a hash of the timestamp or a unique counter) to the actual underlying identifier or URI provided by VS Code for that historical entry. This map is essential for the subsequent `history.getVersionContent` call to retrieve the correct file content.
    *   This temporary storage will be session-specific and ideally held in memory.

### 3. Data Exchange with AI Agent

*   **For User-Initiated Sharing (`history.getSharedContent`):**
    1.  User invokes a VS Code command (e.g., "History Helper: Share historical version...").
    2.  User selects a file and a specific historical version from the Quick Pick.
    3.  The extension reads the content of this selected version using VS Code APIs.
    4.  This content, along with its `filePath`, is temporarily stored in an in-memory variable within the extension (e.g., `sharedContentForAgent = { filePath: "...", content: "..." }`).
    5.  When the AI agent calls `history.getSharedContent`, the extension checks this variable. If `filePathHint` is provided, it's validated. If content is present (and matches hint), it's returned. The variable is then cleared.
*   **For Agent-Requested Data (`history.requestVersions`, `history.getVersionContent`):**
    1.  Agent calls the tool (e.g., `history.requestVersions({ filePath: "..." })`).
    2.  The extension logic activates, triggering the appropriate VS Code UI (e.g., modal notification for consent).
    3.  If user consents, further UI may appear (e.g., Quick Pick for version selection).
    4.  Based on user interaction, the extension gathers the required data (e.g., list of version details or content of a specific version) using VS Code APIs.
    5.  The data is formatted into the JSON structure specified in the tool's output definition.
    6.  This JSON object is then returned to the calling AI agent.
*   **Communication Bridge (Hypothetical):** The extension's responsibility ends with providing the structured data (JSON object) as a return value from the invoked tool function. The AI agent's framework (which integrates the extension's exposed tools) is responsible for the actual transmission and delivery of this data back to the AI model or its controlling logic.

### 4. State Management (within the extension)

*   **Temporary State Required:**
    *   `pendingSharedContent`: An object or variable holding `{ filePath: string, content: string, timestamp?: string }` for content shared by the user via the "Share historical version..." command, awaiting retrieval by `history.getSharedContent`. Only one such item needs to be stored at a time; a new share overwrites a previous one.
    *   `approvedVersionIds`: A short-lived in-memory map, possibly per file path. For example: `Map<filePath, Array<{id: string, internalVsCodeId: any, label: string}>>`. This map stores the list of versions that the user explicitly selected and allowed the agent to see (via `history.requestVersions`) for a specific file. The `id` is what's given to the agent; the `internalVsCodeId` is what the extension uses to fetch content from VS Code.
*   **Clearing State:**
    *   `pendingSharedContent`: Cleared immediately after successful retrieval by `history.getSharedContent`, or after a short timeout (e.g., 5-10 minutes) if not retrieved, or when the user initiates a new sharing action, or when VS Code closes.
    *   `approvedVersionIds`: This list is relevant only for a sequence of `history.requestVersions` followed by `history.getVersionContent`. It can be cleared:
        *   After a `history.getVersionContent` call for one of its IDs (or keep it if agent might ask for another from the same list).
        *   More practically, after a timeout (e.g., 10-15 minutes from the `history.requestVersions` call).
        *   When the user explicitly denies a `history.getVersionContent` request for a file.
        *   When VS Code closes.
    *   The goal is to hold state for the minimum necessary duration to complete an interaction flow.

### 5. Error Handling Logic

*   **No History:** If the agent requests history for a file that has no local history entries, the extension will return an appropriate status to the agent (e.g., `{ status: "error", message: "No local history available for this file." }`). The user may also see a gentle notification if the request was user-initiated.
*   **User Denials:** If the user clicks "Deny" or dismisses a confirmation prompt, the extension will return `{ status: "denied_by_user" }` to the agent. No further action will be taken for that request.
*   **API Failures/Inaccessible History:**
    *   If VS Code APIs used to fetch history (e.g., for listing versions or getting content) fail unexpectedly, the extension will attempt to catch these errors.
    *   It will return an error status to the agent (e.g., `{ status: "error", message: "Failed to access or retrieve file history due to an internal error." }`).
    *   A log (to VS Code's developer console or extension logs) should be created for debugging.
    *   The user might be shown a notification like "History Helper could not access history for [filename]."
*   **Invalid Inputs from Agent:** If the agent provides an invalid `filePath` (e.g., doesn't exist) or an unrecognized `versionId`, the extension will return an error status with a descriptive message.

This conceptual logic will guide the more detailed technical design and implementation.

## Security and Privacy Considerations

The design of this extension prioritizes user control and data privacy. The following principles are central:

1.  **User Control as Paramount:**
    *   The user is ALWAYS the gatekeeper. NO file history content or list of historical versions is EVER accessed or shared with the AI agent without an explicit, per-instance user interaction and confirmation.
    *   This confirmation typically involves the user clicking "Allow" or making a selection in a VS Code notification or Quick Pick dialog that clearly states the intent (e.g., "AI Agent requests access to history for [filename]. Allow?").

2.  **Data Scope and Minimization:**
    *   The extension is designed to access only the minimum data necessary. It will only attempt to access history for the *specific file* that the user has selected (via a VS Code command like "History Helper: Share historical version...") or the file that the AI agent has requested (and the user subsequently approved access for, via a prompt).
    *   The extension will NOT scan the entire `.history` folder, other project files, or any directories.
    *   When content is shared, it is limited to the *specific historical version(s)* explicitly selected or approved by the user.

3.  **Ephemeral Nature of Shared Data (within the extension):**
    *   Any data prepared for the AI agent (such as a list of version IDs or the content of a specific historical file) is held temporarily by the extension, typically in-memory.
    *   This temporary data is cleared under the following conditions to prevent stale or unintended data persistence within the extension's runtime:
        *   After the AI agent successfully retrieves it via the relevant tool call (e.g., `history.getSharedContent` or `history.getVersionContent`).
        *   After a short timeout (e.g., 5-15 minutes, to be fine-tuned) if the agent does not retrieve the data.
        *   If the user initiates a new sharing action for `history.getSharedContent`, the previously shared (but unretrieved) content is replaced.
        *   When the VS Code window/session closes.

4.  **No Persistent Storage of History Content by the Extension:**
    *   The extension itself does NOT store or make copies of file history content to disk, VS Code global state, or workspace state.
    *   It reads from VS Code's own local history store on-demand, only when the user has approved a specific access request, and only for the duration necessary to fulfill that request. The retrieved content is passed to the agent or held ephemerally as described above.

5.  **Agent Responsibilities (Guidance for Agent Developers):**
    *   This extension acts as a user-controlled bridge to VS Code's local file history. While the extension ensures user consent before *providing* data to an AI agent, the agent itself (and its developers) are responsible for handling that data ethically and securely once received.
    *   Agents should be designed to:
        *   Use the historical data only for the specific, user-intended coding assistance task.
        *   Avoid exfiltrating or misusing the received content.
        *   Clearly communicate to the user how they intend to use the historical data, if not already obvious from the context of the request.
    *   This is a guideline for the broader AI agent ecosystem; the extension itself cannot enforce agent-side behavior.

6.  **No External Network Communication (by the extension itself for this feature):**
    *   The core functionality of this extension—accessing local file history based on user permissions and making it available to an AI agent—does not require the extension to make any external network calls to remote servers.
    *   The communication channel between the extension (providing data via tool outputs) and the AI agent (consuming tool outputs) is assumed to be managed by the AI agent's framework, typically operating within the local user environment or a secure sandbox provided by the agent's runtime.

7.  **Transparency:**
    *   All significant actions taken by the extension, especially those involving access to file history or the preparation/sharing of data with an AI agent, will be clearly communicated to the user.
    *   This will primarily be achieved through VS Code's standard notification system (modal dialogs for explicit consent, and non-modal notifications for status updates like "Historical content shared with agent" or "Agent request denied").
    *   Clear and concise language will be used in prompts to ensure the user understands what they are consenting to.

## Documentation Structure (README Outline)

### 1. Extension Name: AI History Helper (Placeholder)
    *   Tagline: Securely connect your AI coding assistant to VS Code's local file history.

### 2. Overview
    *   **Problem Solved:** Enables AI coding agents to securely access specific versions of files from VS Code's local history, which they currently cannot do directly. This helps with tasks like recovering from recent mistakes, understanding code evolution, or referring to uncommitted changes.
    *   **Target Audience:** Developers using VS Code with AI coding assistants who want to provide more context to their agents from local file history.
    *   **Core Benefit:** Provides a user-controlled and secure bridge between AI agents and VS Code's local file history, enhancing the agent's ability to assist with code-related tasks by giving it access to recent, uncommitted changes or historical versions of files.

### 3. Features
    *   User-controlled sharing of specific historical file versions with an AI agent.
    *   Agent-initiated requests for a list of historical versions (requires user approval and selection).
    *   Agent-initiated requests for the content of specific historical versions (requires user approval).
    *   Seamless integration with VS Code UI elements (Command Palette, context menus, notifications, Quick Picks).
    *   Clear and explicit user prompts for all data sharing actions.
    *   Secure, temporary handling of shared data.

### 4. Requirements
    *   VS Code version: ^1.75.0 (or as determined by API availability, especially Timeline APIs).
    *   AI Agent: The AI agent must be capable of using VS Code extension-provided tools/APIs. The agent's developers will need to adapt it to call the tools exposed by this extension (e.g., `history.requestVersions`).

### 5. Installation
    1.  Open VS Code.
    2.  Go to the Extensions view (Ctrl+Shift+X or Cmd+Shift+X).
    3.  Search for "AI History Helper" (or the final extension name).
    4.  Click "Install".
    5.  Reload VS Code if prompted.

### 6. How to Use (For End Users)

    #### 6.1. User-Initiated Sharing
    1.  **Activate:**
        *   Right-click on an active editor tab or a file in the Explorer view and select "History Helper: Share historical version with AI Agent".
        *   Alternatively, open the Command Palette (Ctrl+Shift+P), type "History Helper: Share historical version with AI Agent", and select the command.
    2.  **Select Version:** If the file has local history, a Quick Pick dropdown will appear listing available historical entries (e.g., "5 minutes ago", "Last saved by user"). Select the version you want to share.
    3.  **Inform Agent:** A notification will confirm: "Historical content for [filename] ([timestamp]) is ready. Inform your AI agent it can request this content."
    4.  **Agent Retrieval:** You can then instruct your AI agent (e.g., via chat) to retrieve this content. The agent will use the `history.getSharedContent` tool. For example: "I've shared a previous version of `script.py`, can you look at it?"

    #### 6.2. Responding to Agent Requests
    *   **Request for History List:**
        *   If an AI agent wants to see a list of available historical versions for a file (e.g., `utils.ts`), it will use the `history.requestVersions` tool.
        *   You will see a modal notification: "AI Agent is requesting access to historical versions for `utils.ts`. [Allow] [Deny]".
        *   If you click "Allow", a Quick Pick will show available history entries. You can select one or more versions to make available to the agent.
        *   The agent will then receive the list of IDs/timestamps for the versions you selected.
    *   **Request for Specific Content:**
        *   If an AI agent wants to see the content of a specific historical version (which it might know from a list you previously approved, or if you instructed it to look at a specific time), it will use the `history.getVersionContent` tool.
        *   You will see a modal notification: "AI Agent is requesting to view the content of a historical version of `utils.ts` (e.g., 10 minutes ago). [Allow] [Deny]".
        *   If you click "Allow", the content of that version is shared with the agent for its current task.
        *   If you click "Deny" in any prompt, the agent is informed that the request was denied.

### 7. For AI Agent Developers (Integrating with the Extension)

    #### 7.1. Introduction
    The "AI History Helper" extension exposes a set of tools that your AI agent can call to interact with VS Code's local file history, subject to end-user approval for each action. By integrating these tools, your agent can gain valuable context for tasks like code generation, refactoring, or error analysis.

    #### 7.2. Tool Specifications
    (This section would briefly describe each tool and its parameters/return values, or link to a more detailed document/section containing the full API spec from the "Agent-Facing API/Tool Interface" section of this design document.)

    *   **`history.requestVersions(filePath: string)`:**
        *   Requests a user-approved list of historical version identifiers for the given `filePath`.
        *   Returns: `{ status: "success", versions: [...] }` or `{ status: "denied_by_user" }` or `{ status: "error", message: "..." }`.
    *   **`history.getVersionContent(filePath: string, versionId: string)`:**
        *   Requests user-approved content for a specific `versionId` of the `filePath`.
        *   Returns: `{ status: "success", filePath: "...", versionId: "...", content: "..." }` or denial/error.
    *   **`history.getSharedContent(filePathHint?: string)`:**
        *   Retrieves content that the user has proactively shared via the VS Code UI.
        *   Returns: `{ status: "success", filePath: "...", content: "..." }` or `{ status: "no_content_available" }` or `{ status: "no_matching_content", ... }`.

    #### 7.3. Recommended Workflow/Best Practices for Agents
    *   **Handle User Denials:** Always check the `status` field in the response. If it's `denied_by_user`, respect the user's choice and do not retry without further user instruction.
    *   **Error Handling:** Implement robust error handling for `status: "error"` responses. The `message` field will contain more details.
    *   **Using `filePathHint`:** When calling `history.getSharedContent`, provide the `filePathHint` if your agent expects content for a particular file. This helps ensure the correct content is retrieved if the user interaction context is ambiguous.
    *   **Transparency with Users:** It's good practice for the agent to inform the user *why* it's requesting access to file history, enhancing transparency and trust. For example: "To help you revert the recent changes, I'd like to look at the history of `main.py`. Is that okay?"
    *   **State Intention:** Before calling a tool that will prompt the user, the agent could state its intention, e.g., "I will now ask for a list of recent versions for file X."

### 8. Security and Privacy
    *   **User-Controlled:** You are always in control. The extension will not share any data without your explicit approval for each request.
    *   **Data Minimization:** Only history for the specifically requested/selected file and version(s) is accessed.
    *   **Temporary Handling:** Shared content is handled temporarily by the extension and cleared after use or timeout. The extension does not store your file history.
    *   **Agent Responsibility:** The AI agent you use is responsible for how it handles the content once you've approved its sharing.
    *   (Refer to the full "Security and Privacy Considerations" section in the design document for more details.)

### 9. Troubleshooting
    *   **Issue:** Agent reports "no content available" after I used "Share historical version...".
        *   **Check:** Did you inform the agent to retrieve the content? The agent needs to call `history.getSharedContent`.
        *   **Check:** Was there a long delay? Shared content might time out. Try sharing again.
    *   **Issue:** The list of historical versions is empty or missing recent changes.
        *   **Check:** Ensure VS Code's local file history is enabled and functioning for your files. (Check VS Code settings for "Local History").
        *   **Check:** The file must have been saved at least once to have local history entries.
    *   **Issue:** Extension commands are not visible.
        *   **Check:** Ensure the extension is installed and enabled in the Extensions view. Reload VS Code.

### 10. Contributing (Conceptual)
    *   This is an open-source project. We welcome contributions!
    *   **Repository:** `[Link to GitHub Repository]` (Placeholder)
    *   **Bugs & Features:** Please report bugs or suggest features via the GitHub Issues page.
    *   **Development:** See `CONTRIBUTING.md` in the repository for details on setting up a development environment and submitting pull requests.

### 11. License
    *   MIT License (or chosen license)
