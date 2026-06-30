import { existsSync, readFileSync, readdirSync, statSync, copyFileSync } from 'node:fs';
import { join } from 'node:path';

export interface PrecheckCategory {
    categoryName: string;
    publishTypes: string[];
    subTypes: string[];
}

export interface PrecheckData {
    generatedAt: string;
    baseURL: string;
    project: string;
    env: any;
    categories: PrecheckCategory[];
}

export class PrecheckHelper {
    private static readonly PRECHECK_FILE_PATH = join(
        process.cwd(),
        '.precheck',
        'property-type-manager.latest.json'
    );

    /**
     * Reads the latest precheck JSON data.
     * Auto-fallback: if latest.json is missing, finds and copies the newest JSON.
     */
    static readLatestPrecheck(): PrecheckData {
        // Auto-fallback: if latest.json missing, find newest JSON and copy it
        if (!existsSync(this.PRECHECK_FILE_PATH)) {
            const dir = join(process.cwd(), '.precheck');

            // Check if directory exists
            if (!existsSync(dir)) {
                throw new Error(
                    `❌ Precheck directory not found: ${dir}\n` +
                        `Please run 'npm run e2e:precheck' first to generate environment data.`
                );
            }

            // Find all JSON files
            const candidates = readdirSync(dir).filter((f) => f.endsWith('.json'));

            if (candidates.length === 0) {
                throw new Error(
                    `❌ No precheck JSON files found in ${dir}\n` +
                        `Please run 'npm run e2e:precheck' first to generate environment data.`
                );
            }

            // Sort by modification time (newest first)
            candidates.sort((a, b) => {
                const statA = statSync(join(dir, a));
                const statB = statSync(join(dir, b));
                return statB.mtimeMs - statA.mtimeMs;
            });

            const newestFile = join(dir, candidates[0]);
            console.log(`📝 Latest precheck JSON not found, using newest: ${candidates[0]}`);
            copyFileSync(newestFile, this.PRECHECK_FILE_PATH);
        }

        try {
            const content = readFileSync(this.PRECHECK_FILE_PATH, 'utf-8');
            return JSON.parse(content) as PrecheckData;
        } catch (error) {
            throw new Error(`❌ Failed to parse precheck JSON: ${(error as Error).message}`);
        }
    }

    /**
     * Finds a category by name (case-insensitive).
     */
    static findCategory(data: PrecheckData, name: string): PrecheckCategory | undefined {
        return data.categories.find((c) => c.categoryName.toLowerCase() === name.toLowerCase());
    }

    /**
     * Checks if a publish type exists in a category.
     */
    static hasPublishType(category: PrecheckCategory, type: string): boolean {
        return category.publishTypes.some((t) => t.toLowerCase() === type.toLowerCase());
    }

    /**
     * Finds the first matching subtype from a priority list.
     */
    static findPrioritySubType(
        category: PrecheckCategory,
        priorities: (string | RegExp)[]
    ): string | undefined {
        for (const pattern of priorities) {
            const match = category.subTypes.find((st) => {
                if (typeof pattern === 'string') {
                    return st.toLowerCase().includes(pattern.toLowerCase());
                }
                return pattern.test(st);
            });
            if (match) return match;
        }
        return undefined;
    }
}
