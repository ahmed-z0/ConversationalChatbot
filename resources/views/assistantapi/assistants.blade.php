<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistants List</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .create-button-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .table-container {
            max-height: 400px;
            /* overflow-y: scroll; */
            display: flex;
            justify-content: center;
        }
        .table-responsive {
            width: 100%;
            max-width: 80%; /* Adjust based on your preference */
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="create-button-container">
        <button class="btn btn-primary" onclick="window.location.href='{{ route('create.assistant') }}'">Create Assistant</button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="table-container">
        <div class="table-responsive">
            <table class="table">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Name</th>
                        <th scope="col">Assistant ID</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assistantsList as $index => $assistant)
                        <tr>
                            <th scope="row">{{ $index + 1 }}</th>
                            <td>{{ $assistant['name'] }}</td>
                            <td>{{ $assistant['id'] }}</td>
                                <td>
                                    <a href="{{ route('assistant.use', ['id' => $assistant['id'], 'name' => $assistant['name']]) }}" class="btn btn-sm btn-success">Use</a>
                                <button class="btn btn-sm btn-info" onclick="window.location.href='{{ route('assistant.edit', $assistant['id']) }}'">Edit</button>
                                <form action="{{ route('assistant.delete',  $assistant['id']) }}" method="POST" style="display: inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
