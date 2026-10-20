/**
 * Form-schema preview (Plan P1.4): renders the draft schema the way the customer/driver apps do and applies the
 * same `visible_if` rules as ServiceFormValidator (PHP) and DynamicFormRenderer (Dart).
 */

const isFilled = (value) => !(value === null || value === undefined || value === '' || (Array.isArray(value) && value.length === 0));

/** Booleans, numbers and strings compare by their string form so "1", 1 and true agree with the JSON schema. */
const loose = (value) => {
    if (typeof value === 'boolean') {
        return value ? '1' : '0';
    }

    if (typeof value === 'number') {
        return String(value);
    }

    return typeof value === 'object' && value !== null ? JSON.stringify(value) : String(value);
};

const contains = (haystack, actual) => {
    const values = Array.isArray(actual) ? actual : [actual];

    return values.some((value) => haystack.some((candidate) => loose(value) === loose(candidate)));
};

export function conditionMatches(op, actual, expected) {
    const filled = isFilled(actual);
    const numbers = filled && !Number.isNaN(Number(actual)) && !Number.isNaN(Number(expected));

    switch (op) {
        case 'filled':
            return filled;
        case 'empty':
            return !filled;
        case 'eq':
            return filled && loose(actual) === loose(expected);
        case 'neq':
            return !filled || loose(actual) !== loose(expected);
        case 'in':
            return filled && contains([].concat(expected ?? []), actual);
        case 'not_in':
            return !filled || !contains([].concat(expected ?? []), actual);
        case 'gt':
            return numbers && Number(actual) > Number(expected);
        case 'gte':
            return numbers && Number(actual) >= Number(expected);
        case 'lt':
            return numbers && Number(actual) < Number(expected);
        case 'lte':
            return numbers && Number(actual) <= Number(expected);
        default:
            return false;
    }
}

/** Fields visible for the current answers, in schema order (hidden fields do not count as answered). */
export function visibleFields(fields, answers) {
    const visible = [];
    const effective = {};

    for (const field of fields) {
        if (field.visible_if && !conditionMatches(field.visible_if.op, effective[field.visible_if.field], field.visible_if.value)) {
            continue;
        }

        visible.push(field);

        if (answers[field.key] !== undefined) {
            effective[field.key] = answers[field.key];
        }
    }

    return visible;
}

export function schemaPreview(fields = [], locale = 'ar') {
    return {
        fields,
        locale,
        answers: {},

        init() {
            for (const field of this.fields) {
                if (field.type === 'multiselect' || field.type === 'photo' || field.type === 'item_checklist') {
                    this.answers[field.key] = [];
                } else if (field.type === 'contact') {
                    this.answers[field.key] = { name: '', phone: '' };
                } else if (field.type === 'dimensions') {
                    this.answers[field.key] = { length: '', width: '', height: '' };
                }
            }
        },

        get visible() {
            return visibleFields(this.fields, this.answers);
        },

        isVisible(field) {
            return this.visible.some((candidate) => candidate.key === field.key);
        },

        text(map) {
            return (map && (map[this.locale] || map.ar || map.en)) || '';
        },

        toggleOption(field, value) {
            const current = this.answers[field.key] ?? [];
            this.answers[field.key] = current.includes(value) ? current.filter((item) => item !== value) : [...current, value];
        },
    };
}
