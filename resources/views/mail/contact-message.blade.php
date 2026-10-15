A new message arrived from the Medicare contact form.

Name: {{ $contactMessage->name }}
Email: {{ $contactMessage->email }}
Phone: {{ $contactMessage->phone ?: 'Not provided' }}
Topic: {{ $contactMessage->subjectLabel() }}

{{ $contactMessage->body }}
