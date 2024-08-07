<div>
    <form action="{{ route('tenants.store') }}" method="POST">
        @csrf
        <label for="name">Name:</label>
        <input type="text" name="name" id="name" required>
        <br>
        <label for="domain">Domain:</label>
        <textarea name="domain" id="domain" required></textarea>
        <br>
        <button type="submit">Create</button>
    </form>
</div>
