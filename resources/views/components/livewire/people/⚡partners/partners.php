<?php

declare(strict_types=1);

use App\Models\Couple;
use App\Models\Person;
use Livewire\Attributes\On;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use Interactions;

    public Person $person;

    #[On('couple_added')]
    #[On('couple_updated')]
    #[On('couple_deleted')]
    public function refreshPartners(): void
    {
        // optionally refresh any data here
        // Livewire will re-render automatically
    }

    public function confirm(int $id, string $name): void
    {
        $this->authorize('delete', $this->findCouple($id));

        $this->dialog()
            ->question(__('app.attention') . '!', __('app.are_you_sure'))
            ->confirm(__('app.delete_yes'))
            ->cancel(__('app.cancel'))
            ->hook([
                'ok' => [
                    'method' => 'delete',
                    'params' => [
                        'id'   => $id,
                        'name' => $name,
                    ],
                ],
            ])
            ->send();
    }

    /**
     * @param  array{id: int, name: string}  $couple
     */
    public function delete(array $couple): void
    {
        $model = $this->findCouple($couple['id']);

        $this->authorize('delete', $model);

        $model->delete();

        $this->toast()->success(__('app.delete'), e($model->name) . ' ' . __('app.deleted') . '.')->send();

        $this->dispatch('couple_deleted');
    }

    /**
     * Only couples this person is part of may be targeted from their partners list.
     */
    protected function findCouple(int $id): Couple
    {
        return Couple::where(function ($q): void {
            $q->where('person1_id', $this->person->id)
                ->orWhere('person2_id', $this->person->id);
        })->findOrFail($id);
    }
};
