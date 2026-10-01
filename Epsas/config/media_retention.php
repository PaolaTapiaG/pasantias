<?php

return [
    'personal_photo_days_after_inactivity' => (int) env('RETENTION_PERSONAL_PHOTO_DAYS', 365),
    'technical_evidence_days_after_closure' => (int) env('RETENTION_TECHNICAL_EVIDENCE_DAYS', 730),
    'approved_payment_proof_days' => (int) env('RETENTION_APPROVED_PAYMENT_PROOF_DAYS', 3650),
    'rejected_payment_proof_days' => (int) env('RETENTION_REJECTED_PAYMENT_PROOF_DAYS', 365),
];
