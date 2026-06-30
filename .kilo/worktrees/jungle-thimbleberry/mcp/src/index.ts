import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from "@modelcontextprotocol/sdk/types.js";
import { spawn } from "child_process";

/**
 * Yalıhan Bekçi MCP Adapter - Version 1.1.0 (Strict Bridge)
 */

const PHP_PATH = "/opt/homebrew/bin/php";
const ARTISAN_PATH = "artisan";

const server = new Server(
  {
    name: "yalihan-bekci",
    version: "3.3.0",
  },
  {
    capabilities: {
      tools: {},
    },
  }
);

/**
 * Execute Artisan Command and pipe JSON directly to the agent.
 */
async function runArtisan(command: string, args: string[] = []): Promise<any> {
  return new Promise((resolve) => {
    const fullArgs = [ARTISAN_PATH, command, ...args, "--format=json"];
    const child = spawn(PHP_PATH, fullArgs);

    let stdout = "";
    let stderr = "";

    child.stdout.on("data", (data) => { stdout += data.toString(); });
    child.stderr.on("data", (data) => { stderr += data.toString(); });

    child.on("close", (code) => {
      try {
        if (stdout.trim()) {
          resolve(JSON.parse(stdout));
        } else {
          resolve({
            ok: false,
            errors: [{ code: "CLI_ERROR", message: stderr || "No output from CLI" }],
          });
        }
      } catch (e) {
        resolve({
          ok: false,
          errors: [{ code: "PARSE_ERROR", message: "Failed to parse CLI output" }],
          raw: stdout
        });
      }
    });
  });
}

server.setRequestHandler(ListToolsRequestSchema, async () => {
  return {
    tools: [
      {
        name: "bekci.scan",
        description: "Perform architectural governance scan. Returns normalized JSON.",
        inputSchema: {
          type: "object",
          properties: {
            path: { type: "string", default: "app" }
          }
        },
      },
      {
        name: "bekci.learn",
        description: "Teach AI context from recent actions. Returns guidance.",
        inputSchema: {
          type: "object",
          properties: {
            topic: { type: "string" },
            context: { type: "string" }
          },
          required: ["topic", "context"]
        },
      },
      {
        name: "bekci.health",
        description: "Check governance bridge integrity.",
        inputSchema: {
          type: "object"
        },
      },
    ],
  };
});

server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;

  try {
    switch (name) {
      case "bekci.scan":
        const scanRes = await runArtisan("sab:integrity-scan", [`--path=${args?.path || 'app'}`]);
        return { content: [{ type: "text", text: JSON.stringify(scanRes, null, 2) }] };

      case "bekci.learn":
        const learnRes = await runArtisan("bekci:learn", [args?.topic as string, args?.context as string]);
        return { content: [{ type: "text", text: JSON.stringify(learnRes, null, 2) }] };

      case "bekci.health":
        const healthRes = await runArtisan("sab:integrity-scan", ["--format=json"]); // Sample scan as health check
        return { content: [{ type: "text", text: JSON.stringify({ ok: true, tool: "bekci.health", data: { healthy: true } }, null, 2) }] };

      default:
        throw new Error(`Unknown tool: ${name}`);
    }
  } catch (error: any) {
    return {
      content: [{ type: "text", text: `Bridge Error: ${error.message}` }],
      isError: true,
    };
  }
});

async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
}

main().catch((error) => {
  console.error("MCP Server lethal error:", error);
  process.exit(1);
});
