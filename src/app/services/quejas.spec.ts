import { TestBed } from '@angular/core/testing';

import { Quejas } from './quejas';

describe('Quejas', () => {
  let service: Quejas;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Quejas);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
