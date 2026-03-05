import './bootstrap';
import { ozoSignature } from './ozo-signature';

document.addEventListener('alpine:init', () => {
    Alpine.data('ozoSignature', () => ozoSignature());
});