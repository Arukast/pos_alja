import { expect, it } from 'vitest';

import { selectableAttributes } from './attributeOptions';

const base = { price: '0.00', cost: '0.00', type_name: 'Finishing', branch_id: null, branch: null };
const attributes = [
    { ...base, id: 'a1', name: 'Glossy', type: 'finishing', is_active: true },
    { ...base, id: 'a2', name: 'Matte', type: 'finishing', is_active: false },
    { ...base, id: 'a3', name: 'X-Banner', type: 'display', is_active: true },
    { ...base, id: 'a4', name: 'Old Display', type: 'display', is_active: false },
];

it('offers active options plus inactive options already selected for this row', () => {
    expect(selectableAttributes(attributes, 'finishing', []).map((a) => a.id)).toEqual(['a1']);
    expect(selectableAttributes(attributes, 'finishing', ['a2']).map((a) => a.id)).toEqual(['a1', 'a2']);
    expect(selectableAttributes(attributes, 'display', ['a1']).map((a) => a.id)).toEqual(['a3']);
});
