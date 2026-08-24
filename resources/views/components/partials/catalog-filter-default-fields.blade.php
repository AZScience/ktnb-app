@props(['fields' => []])

<div class="grid grid-cols-1 gap-4 overflow-y-auto p-6 text-sm md:grid-cols-2">
    @foreach ($fields as $field)
        <div class="space-y-2">
            <label class="flex items-center gap-2 font-medium text-gray-700">
                <x-form-field-icon :name="$field['icon'] ?? 'tag'" :tone="$field['tone'] ?? 'blue'" class="h-4 w-4" />
                <span>{{ $field['label'] }}</span>
            </label>
            @if (($field['type'] ?? 'text') === 'select')
                <select class="nttu-form-control"
                        :value="filters['{{ $field['key'] }}'] || ''"
                        @change="setFilter('{{ $field['key'] }}', $event.target.value)">
                    @foreach ($field['options'] ?? [] as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            @else
                <input type="text" class="nttu-form-control"
                       :value="filters['{{ $field['key'] }}'] || ''"
                       placeholder="Lọc {{ $field['label'] }}..."
                       @input="setFilter('{{ $field['key'] }}', $event.target.value)">
            @endif
        </div>
    @endforeach
</div>
