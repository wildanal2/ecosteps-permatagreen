<flux:navbar.item href="{{ route('admin.daily-report') }}" :current="request()->routeIs('admin.daily-report')" wire:navigate>Gallery Report</flux:navbar.item>
<flux:navbar.item href="{{ route('admin.daily-report-list') }}" :current="request()->routeIs('admin.daily-report-list')">Data List Participant</flux:navbar.item>
