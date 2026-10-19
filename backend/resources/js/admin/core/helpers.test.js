import { describe, expect, it } from 'vitest';
import { keyToName, nameToKey, serializeFields, toSearchParams } from './dom.js';
import { fromMinor, toMinor } from '../components/ui.js';

describe('field key mapping', () => {
    it('maps bracket names to Laravel dot keys and back', () => {
        expect(nameToKey('title[ar]')).toBe('title.ar');
        expect(nameToKey('items[0][name]')).toBe('items.0.name');
        expect(nameToKey('tags[]')).toBe('tags');
        expect(keyToName('items.0.name')).toBe('items[0][name]');
        expect(keyToName('code')).toBe('code');
    });
});

describe('serializeFields', () => {
    it('builds nested filters and skips empty values', () => {
        document.body.innerHTML = `
            <form>
                <input name="filters[code]" value="AB">
                <input name="filters[empty]" value="">
                <select name="filters[statuses][]" multiple><option value="a" selected></option><option value="b" selected></option></select>
                <input name="filters[created_at][from]" value="2026-01-01">
                <input type="radio" name="filters[featured]" value="1" checked>
                <input type="radio" name="filters[featured]" value="0">
            </form>`;

        const data = serializeFields(document.querySelector('form'));

        expect(data).toEqual({ filters: { code: 'AB', statuses: ['a', 'b'], created_at: { from: '2026-01-01' }, featured: '1' } });
        expect(toSearchParams(data).toString()).toBe(
            'filters%5Bcode%5D=AB&filters%5Bstatuses%5D%5B%5D=a&filters%5Bstatuses%5D%5B%5D=b&filters%5Bcreated_at%5D%5Bfrom%5D=2026-01-01&filters%5Bfeatured%5D=1',
        );
    });
});

describe('money input conversion', () => {
    it('converts decimal display to minor units without floats', () => {
        expect(toMinor('12.5', 2)).toBe('1250');
        expect(toMinor('0.07', 2)).toBe('7');
        expect(toMinor('١٢٫٥٠', 2)).toBe('1250');
        expect(toMinor('1,234.56', 2)).toBe('123456');
        expect(toMinor('1.234', 2)).toBe('');
        expect(toMinor('abc', 2)).toBe('');
        expect(toMinor('15', 0)).toBe('15');
        expect(fromMinor('1250', 2)).toBe('12.50');
        expect(fromMinor('7', 2)).toBe('0.07');
        expect(fromMinor('', 2)).toBe('');
    });
});
