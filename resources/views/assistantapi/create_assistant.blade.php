<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Assistant</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <h2>Create a New Assistant</h2>
    <div id="message"></div>
    <form id="createAssistantForm" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="name">Assistant Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
        </div>
        <div class="form-group">
            <label for="file">Upload File (Optional)</label>
            <input type="file" class="form-control-file" id="file" name="file">
        </div>
        <button type="submit" class="btn btn-primary">Create Assistant</button>
    </form>
</div>

<script>
$(document).ready(function() {
    $('#createAssistantForm').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: '{{ route("assistant.store") }}',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                $('#message').html('<div class="alert alert-success">' + response.message + '</div>');
            },
            error: function(xhr) {
                $('#message').html('<div class="alert alert-danger">' + xhr.responseJSON.message + '</div>');
            }
        });
    });
});
</script>
</body>
</html>
