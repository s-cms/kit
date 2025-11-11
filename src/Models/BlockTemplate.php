<?php

namespace SmartCms\Kit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use SmartCms\Kit\Support\Traits\HasBlocks;

/**
 * Class BlockTemplate
 *
 * @property int $id The unique identifier for the model.
 * @property string $name The name of the template.
 * @property string|null $type The page type this template is for (page, category, or custom types). Null means universal.
 * @property \DateTime $created_at The date and time when the model was created.
 * @property \DateTime $updated_at The date and time when the model was last updated.
 * @property-read \Illuminate\Database\Eloquent\Collection $blocks The blocks in this template.
 */
class BlockTemplate extends Model
{
    use HasBlocks;
    use HasFactory;

    protected $guarded = [];

    /**
     * Apply this template to a page (force replace all blocks).
     *
     * @param  Page  $page
     * @return void
     */
    public function applyToPage(Page $page): void
    {
        // Detach all existing blocks
        $page->blocks()->detach();

        // Attach template blocks with their pivot data
        foreach ($this->blocks as $block) {
            $page->blocks()->attach($block->id, [
                'status' => $block->pivot->status,
                'sorting' => $block->pivot->sorting,
                'show_from' => $block->pivot->show_from,
                'show_until' => $block->pivot->show_until,
            ]);
        }
    }

    /**
     * Get the blocks data for attachment (without executing attachment).
     * Returns array suitable for attach() method.
     *
     * @return array
     */
    public function getBlocksForAttachment(): array
    {
        $attachData = [];

        foreach ($this->blocks as $block) {
            $attachData[$block->id] = [
                'status' => $block->pivot->status,
                'sorting' => $block->pivot->sorting,
                'show_from' => $block->pivot->show_from,
                'show_until' => $block->pivot->show_until,
            ];
        }

        return $attachData;
    }
}
