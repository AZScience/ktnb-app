@php
    $employee = $employee ?? null;
@endphp

<div class="max-w-2xl nttu-card p-6">
    <form method="POST" action="{{ $employee ? route('employees.update', $employee) : route('employees.store') }}" class="space-y-4">
        @csrf
        @if ($employee)
            @method('PUT')
        @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-form-label>Mã nhân viên</x-form-label>
                <input name="employee_id" value="{{ old('employee_id', $employee?->employee_id) }}"
                       class="nttu-form-control" required>
                @error('employee_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <x-form-label>Email</x-form-label>
                <input name="email" type="email" value="{{ old('email', $employee?->email) }}"
                       class="nttu-form-control" required>
                @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <x-form-label>Họ và tên</x-form-label>
            <input name="name" value="{{ old('name', $employee?->name) }}"
                   class="nttu-form-control" required>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-form-label>Biệt danh</x-form-label>
                <input name="nickname" value="{{ old('nickname', $employee?->nickname) }}"
                       class="nttu-form-control">
            </div>
            <div>
                <x-form-label>Chức vụ</x-form-label>
                <input name="position" value="{{ old('position', $employee?->position) }}"
                       class="nttu-form-control">
            </div>
        </div>

        <div>
            <x-form-label>Vai trò</x-form-label>
            <select name="role_id" class="nttu-form-control">
                <option value="">— Chọn —</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id', $employee?->role_id) === $role->id)>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-form-label>Điện thoại</x-form-label>
                <input name="phone" value="{{ old('phone', $employee?->phone) }}"
                       class="nttu-form-control">
            </div>
            <div>
                <x-form-label>Sinh nhật</x-form-label>
                <input name="birth_date" value="{{ old('birth_date', $employee?->birth_date) }}"
                       class="nttu-form-control">
            </div>
        </div>

        <div>
            <x-form-label>Địa chỉ</x-form-label>
            <textarea name="address" rows="2" class="nttu-form-control">{{ old('address', $employee?->address) }}</textarea>
        </div>

        <div>
            <x-form-label>Ghi chú</x-form-label>
            <textarea name="note" rows="2" class="nttu-form-control">{{ old('note', $employee?->note) }}</textarea>
        </div>

        <x-form-actions cancel-href="{{ route('employees.index') }}" />
    </form>
</div>
