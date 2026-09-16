<?php

declare(strict_types=1);

namespace App\Domain\Ilan\ValueObjects;

/**
 * FieldDefinition — Pure Domain Value Object for a Complete Form Field Definition
 */
final class FieldDefinition
{
    private FieldKey $key;
    private string $name;
    private string $category;
    private ValidationRule $rule;
    private ?string $icon;
    private int $displayOrder;
    private bool $searchable;
    private bool $showInCard;
    private bool $aiAutoFill;

    private ?string $placeholder;
    private ?string $helpText;
    private ?array $visibleIf;
    private ?array $requiredIf;
    private ?string $dependsOn;
    private bool $aiSuggestion;
    private ?string $aiPromptKey;

    public function __construct(
        FieldKey $key,
        string $name,
        string $category,
        ValidationRule $rule,
        ?string $icon = null,
        int $displayOrder = 0,
        bool $searchable = false,
        bool $showInCard = false,
        bool $aiAutoFill = false,
        ?string $placeholder = null,
        ?string $helpText = null,
        ?array $visibleIf = null,
        ?array $requiredIf = null,
        ?string $dependsOn = null,
        bool $aiSuggestion = false,
        ?string $aiPromptKey = null
    ) {
        $this->key = $key;
        $this->name = $name;
        $this->category = $category;
        $this->rule = $rule;
        $this->icon = $icon;
        $this->displayOrder = $displayOrder;
        $this->searchable = $searchable;
        $this->showInCard = $showInCard;
        $this->aiAutoFill = $aiAutoFill;
        $this->placeholder = $placeholder;
        $this->helpText = $helpText;
        $this->visibleIf = $visibleIf;
        $this->requiredIf = $requiredIf;
        $this->dependsOn = $dependsOn;
        $this->aiSuggestion = $aiSuggestion;
        $this->aiPromptKey = $aiPromptKey;
    }

    public function key(): FieldKey
    {
        return $this->key;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function rule(): ValidationRule
    {
        return $this->rule;
    }

    public function icon(): ?string
    {
        return $this->icon;
    }

    public function displayOrder(): int
    {
        return $this->displayOrder;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function showInCard(): bool
    {
        return $this->showInCard;
    }

    public function isAiAutoFill(): bool
    {
        return $this->aiAutoFill;
    }

    public function placeholder(): ?string
    {
        return $this->placeholder;
    }

    public function helpText(): ?string
    {
        return $this->helpText;
    }

    public function visibleIf(): ?array
    {
        return $this->visibleIf;
    }

    public function requiredIf(): ?array
    {
        return $this->requiredIf;
    }

    public function dependsOn(): ?string
    {
        return $this->dependsOn;
    }

    public function isAiSuggestion(): bool
    {
        return $this->aiSuggestion;
    }

    public function aiPromptKey(): ?string
    {
        return $this->aiPromptKey;
    }

    public function toArray(): array
    {
        return [
            'field_slug'     => $this->key->value(),
            'field_name'     => $this->name,
            'field_category' => $this->category,
            'field_type'     => $this->rule->type(),
            'required'       => $this->rule->isRequired(),
            'field_options'  => $this->rule->options(),
            'field_unit'     => $this->rule->unit(),
            'field_icon'     => $this->icon,
            'display_order'  => $this->displayOrder,
            'searchable'     => $this->searchable,
            'show_in_card'   => $this->showInCard,
            'ai_auto_fill'   => $this->aiAutoFill,
            'placeholder'    => $this->placeholder,
            'help_text'      => $this->helpText,
            'visible_if'     => $this->visibleIf,
            'required_if'    => $this->requiredIf,
            'depends_on'     => $this->dependsOn,
            'ai_suggestion'  => $this->aiSuggestion,
            'ai_prompt_key'  => $this->aiPromptKey,
        ];
    }
}
