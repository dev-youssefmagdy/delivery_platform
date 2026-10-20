import { describe, expect, it } from 'vitest';
import { conditionMatches, schemaPreview, visibleFields } from './schema-preview.js';

describe('conditionMatches', () => {
    it('mirrors the PHP VisibilityOperator semantics', () => {
        expect(conditionMatches('eq', true, 1)).toBe(true);
        expect(conditionMatches('eq', 5, '5')).toBe(true);
        expect(conditionMatches('eq', null, 'x')).toBe(false);
        expect(conditionMatches('neq', null, 'x')).toBe(true);
        expect(conditionMatches('in', ['a', 'b'], ['b', 'c'])).toBe(true);
        expect(conditionMatches('not_in', 'a', ['b'])).toBe(true);
        expect(conditionMatches('gt', 3, 2)).toBe(true);
        expect(conditionMatches('gte', 2, 2)).toBe(true);
        expect(conditionMatches('lt', '', 2)).toBe(false);
        expect(conditionMatches('lte', 2, 2)).toBe(true);
        expect(conditionMatches('filled', [], null)).toBe(false);
        expect(conditionMatches('empty', '', null)).toBe(true);
    });
});

describe('visibleFields', () => {
    const fields = [
        { key: 'elevator', type: 'boolean' },
        { key: 'floor', type: 'integer', visible_if: { field: 'elevator', op: 'eq', value: false } },
        { key: 'floor_note', type: 'text', visible_if: { field: 'floor', op: 'gte', value: 3 } },
    ];

    it('hides conditional fields until their condition holds and chains through hidden fields', () => {
        expect(visibleFields(fields, {}).map((f) => f.key)).toEqual(['elevator']);
        expect(visibleFields(fields, { elevator: false }).map((f) => f.key)).toEqual(['elevator', 'floor']);
        expect(visibleFields(fields, { elevator: false, floor: 4 }).map((f) => f.key)).toEqual(['elevator', 'floor', 'floor_note']);
        expect(visibleFields(fields, { elevator: true, floor: 4 }).map((f) => f.key)).toEqual(['elevator']);
    });
});

describe('schemaPreview', () => {
    it('falls back to Arabic when the locale has no text and seeds structured answers', () => {
        const preview = schemaPreview([{ key: 'who', type: 'contact' }, { key: 'size', type: 'dimensions' }], 'en');
        preview.init();

        expect(preview.text({ ar: 'عربي' })).toBe('عربي');
        expect(preview.answers.who).toEqual({ name: '', phone: '' });
        expect(preview.answers.size).toEqual({ length: '', width: '', height: '' });
    });
});
