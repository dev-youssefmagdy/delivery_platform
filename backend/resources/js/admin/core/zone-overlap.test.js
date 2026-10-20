import { describe, expect, it } from 'vitest';
import { overlapMessage } from './zone-overlap.js';

describe('overlapMessage', () => {
    it('is empty without overlaps', () => {
        expect(overlapMessage('Overlaps:', [])).toBe('');
        expect(overlapMessage('Overlaps:', undefined)).toBe('');
    });

    it('lists zone and city names', () => {
        expect(overlapMessage('Overlaps:', [{ name: 'A', city: 'Cairo' }, { name: 'B', city: 'Giza' }])).toBe('Overlaps: A (Cairo)، B (Giza)');
    });
});
