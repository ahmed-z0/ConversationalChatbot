<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Speech to Text with Silence Detection</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    
</head>
<body>
	<div class="container">
        <section class="...">
            <div class="...">
              <div class="...">
                <div class="...">
                  <div class="...">
                    <div class="...">
                      <div>
                        <p class="...">Laravel Streaming OpenAI</p>
                        <p class="...">
                          Streaming OpenAI Responses in Laravel with Server-Sent Events
                          (SSE).
                          <a class="..." href="...">Read tutorial here</a>
                        </p>
                        <p id="question" class="..."></p>
                        <p id="result" class="..."></p>
                      </div>
                      <form id="form-question" class="...">
                        <input
                          required
                          type="text"
                          name="input"
                          placeholder="Type your question here!"
                          class="..."
                        />
                        <button type="submit" href="#" class="...">
                          Submit
                          <span aria-hidden="true"> → </span>
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>
          
       
        </div>

{{-- -------------------------For chat bot------------------------ --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
  $("form").on("submit", function(event) {
    event.preventDefault();

    var input = $(this).find("input[name='input']").val(); // Assuming 'input' is the name attribute of your input field
    if (input === "") return;

    $("#question").text(input); // Assuming there's an element with the id 'question' to display the input
    $(this).find("input[name='input']").val(""); // Clear the input field

    var queryQuestion = encodeURIComponent(input);
    var source = new EventSource("/ask?question=" + queryQuestion);

    source.addEventListener("update", function(event) {
      if (event.data === "<END_STREAMING_SSE>") {
        source.close();
        return;
      }
      $("#result").append(event.data); // Append the data to the result element
    });
  });
});
</script>

    
</body>
</html>
