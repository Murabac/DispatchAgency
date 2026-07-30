<x-mail::message>
# {{ $documentLabel }} {{ $documentNumber }}

Dear {{ $clientName }},

Please find attached your {{ strtolower($documentLabel) }} **{{ $documentNumber }}** from {{ $businessName }}.

@if (filled($customMessage))
{{ $customMessage }}
@endif

If you have any questions, reply to this email.

Thanks,<br>
{{ $businessName }}
</x-mail::message>
