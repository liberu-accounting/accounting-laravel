<div class="container mx-auto mt-2">
    <h2 class="text-2xl font-bold mb-4">Chart of Accounts</h2>
    <p class="mb-8">Manage your account categories and subcategories.</p>
    <div class="chart-of-accounts grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="card bg-white shadow-md rounded-lg p-4">
            <h5 class="text-lg font-bold mb-2">Categories</h5>
            <ul class="list-disc pl-5">
                @foreach($categories as $category)
                    <li class="mb-2">
                        {{ $category->name }}
                        @if($category->children->count() > 0)
                            <ul class="list-circle pl-5 mt-1">
                                @foreach($category->children as $subcategory)
                                    <li>{{ $subcategory->name }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card bg-white shadow-md rounded-lg p-4">
            <h5 class="text-lg font-bold mb-2">Add New Category</h5>
            <form wire:submit.prevent="addCategory">
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" id="name" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                </div>
                <div class="mb-4">
                    <label for