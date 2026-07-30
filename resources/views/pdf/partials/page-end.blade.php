@php
    $bankDetails = $bankDetails ?? null;
    $notes = $notes ?? null;
@endphp
<div class="page-end">
    @if ($bankDetails)
        <div class="notes-block">
            <div class="notes-title">Bank details</div>
            {{ preg_replace('/\s+/', ' ', $bankDetails) }}
        </div>
    @endif

    @if ($notes)
        <div class="notes-block">
            <div class="notes-title">Notes</div>
            {{ preg_replace('/\s+/', ' ', $notes) }}
        </div>
    @endif

    <div class="bottom-block">
        <table class="sign-row">
            <tr>
                <td>
                    <div class="sign-line">Authorized signature</div>
                </td>
                <td class="gap"></td>
                <td>
                    <div class="sign-line">Company stamp</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Dispatch Agency &copy; {{ date('Y') }}
    </div>
</div>
