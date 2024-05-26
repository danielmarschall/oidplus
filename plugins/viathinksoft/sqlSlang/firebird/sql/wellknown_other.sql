-- This file (wellknown_other_firebird.sql) contains ASN.1 and IRI names of OIDs which are either
-- a) Root OIDs
-- b) Unicode labels which are long arcs
-- c) Standardized ASN.1 identifiers
-- d) OIDs where potential users of this software can register OIDs in these arcs (e.g. an "identified organization" arc)
-- Use the tool dev/generate_wellknown_other_firebird to generate this file

-- 0
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0', 'itu-t', '1', '1');
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0', 'ccitt', '1', '1');
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0', 'itu-r', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0', 'ITU-T', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0', 'ITU-R', '0', '1');

-- 0.0
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0', 'recommendation', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0', 'Recommendation', '0', '1');

-- 0.0.1
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.1', 'a', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.1', 'A', '0', '1');

-- 0.0.2
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.2', 'b', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.2', 'B', '0', '1');

-- 0.0.3
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.3', 'c', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.3', 'C', '0', '1');

-- 0.0.4
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.4', 'd', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.4', 'D', '0', '1');

-- 0.0.5
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.5', 'e', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.5', 'E', '0', '1');

-- 0.0.6
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.6', 'f', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.6', 'F', '0', '1');

-- 0.0.7
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.7', 'g', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.7', 'G', '0', '1');

-- 0.0.8
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.8', 'h', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.8', 'H', '0', '1');

-- 0.0.9
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.9', 'i', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.9', 'I', '0', '1');

-- 0.0.10
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.10', 'j', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.10', 'J', '0', '1');

-- 0.0.11
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.11', 'k', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.11', 'K', '0', '1');

-- 0.0.12
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.12', 'l', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.12', 'L', '0', '1');

-- 0.0.13
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.13', 'm', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.13', 'M', '0', '1');

-- 0.0.14
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.14', 'n', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.14', 'N', '0', '1');

-- 0.0.15
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.15', 'o', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.15', 'O', '0', '1');

-- 0.0.16
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.16', 'p', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.16', 'P', '0', '1');

-- 0.0.17
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.17', 'q', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.17', 'Q', '0', '1');

-- 0.0.18
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.18', 'r', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.18', 'R', '0', '1');

-- 0.0.19
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.19', 's', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.19', 'S', '0', '1');

-- 0.0.20
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.20', 't', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.20', 'T', '0', '1');

-- 0.0.21
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.21', 'u', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.21', 'U', '0', '1');

-- 0.0.22
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.22', 'v', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.22', 'V', '0', '1');

-- 0.0.23
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.23', 'w', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.23', 'W', '0', '1');

-- 0.0.24
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.24', 'x', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.24', 'X', '0', '1');

-- 0.0.25
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.25', 'y', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.25', 'Y', '0', '1');

-- 0.0.26
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.0.26', 'z', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.0.26', 'Z', '0', '1');

-- 0.1
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.1', 'question', '1', '1');

-- 0.2
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.2', 'administration', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.2', 'Administration', '0', '1');

-- 0.3
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.3', 'network-operator', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.3', 'Network-Operator', '0', '1');

-- 0.4
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.4', 'identified-organization', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:0.4', 'Identified-Organization', '0', '1');

-- 0.4.0
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.4.0', 'etsi', '0', '1');

-- 0.4.0.127
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.4.0.127', 'reserved', '0', '1');

-- 0.4.0.127.0
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:0.4.0.127.0', 'etsi-identified-organization', '0', '1');

-- 1
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1', 'iso', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:1', 'ISO', '0', '1');

-- 1.0
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.0', 'standard', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:1.0', 'Standard', '0', '1');

-- 1.1
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.1', 'registration-authority', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:1.1', 'Registration-Authority', '0', '1');

-- 1.1.19785
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.1.19785', 'cbeff', '0', '1');

-- 1.1.19785.0
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.1.19785.0', 'biometric-organization', '0', '1');
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.1.19785.0', 'organization', '0', '1');
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.1.19785.0', 'organizations', '0', '1');

-- 1.2
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.2', 'member-body', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:1.2', 'Member-Body', '0', '1');

-- 1.2.276.0
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.2.276.0', 'din-certco', '0', '1');

-- 1.2.616.1
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.2.616.1', 'organization', '0', '1');

-- 1.2.826.0
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.2.826.0', 'national', '0', '1');

-- 1.2.826.0.1
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.2.826.0.1', 'eng-ltd', '0', '1');

-- 1.2.840.1
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.2.840.1', 'organization', '0', '1');

-- 1.2.840.113556
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.2.840.113556', 'microsoft', '0', '1');

-- 1.2.840.113556.2
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.2.840.113556.2', 'dicom', '0', '1');

-- 1.3
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3', 'identified-organization', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:1.3', 'Identified-Organization', '0', '1');

-- 1.3.6
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.6', 'dod', '0', '1');

-- 1.3.6.1
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1', 'internet', '0', '1');

-- 1.3.6.1.2
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.2', 'mgmt', '0', '1');

-- 1.3.6.1.2.1
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.2.1', 'mib-2', '0', '1');

-- 1.3.6.1.4
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4', 'private', '0', '1');

-- 1.3.6.1.4.1
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1', 'enterprise', '0', '1');

-- 1.3.6.1.4.1.12798.1
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.12798.1', 'member', '0', '1');

-- 1.3.6.1.4.1.37476.9000
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.37476.9000', 'freeoid', '0', '1');
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.37476.9000', 'freesub', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:1.3.6.1.4.1.37476.9000', 'FreeOID', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:1.3.6.1.4.1.37476.9000', 'FreeSub', '0', '1');

-- 1.3.6.1.4.1.37553.8
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.37553.8', 'weid', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:1.3.6.1.4.1.37553.8', 'weid', '0', '1');

-- 1.3.6.1.4.1.37553.8.8
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.37553.8.8', 'private-weid', '0', '1');

-- 1.3.6.1.4.1.37553.8.9
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.37553.8.9', 'ns', '0', '1');

-- 1.3.6.1.4.1.37553.8.9.17704
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.37553.8.9.17704', 'dns', '0', '1');

-- 1.3.6.1.4.1.37553.8.9.1439221
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.37553.8.9.1439221', 'uuid', '0', '1');

-- 1.3.6.1.4.1.61117.9000
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.61117.9000', 'x-requested', '0', '1');

-- 1.3.12
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.12', 'icd-ecma', '0', '1');
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.12', 'ecma', '0', '1');

-- 1.3.12.2
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.12.2', 'member-company', '0', '1');

-- 1.3.60
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.60', 'duns', '0', '1');

-- 1.3.88
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.88', 'ean', '0', '1');

-- 1.3.148
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:1.3.148', 'dnic', '0', '1');

-- 2
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2', 'joint-iso-itu-t', '1', '1');
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2', 'joint-iso-ccitt', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2', 'Joint-ISO-ITU-T', '0', '1');

-- 2.1
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.1', 'asn1', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.1', 'ASN.1', '1', '1');

-- 2.16
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.16', 'country', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.16', 'Country', '1', '1');

-- 2.16.158.1
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.16.158.1', 'organization', '0', '1');

-- 2.16.344.1
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.16.344.1', 'organization', '0', '1');

-- 2.16.840.1
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.16.840.1', 'organization', '0', '1');

-- 2.16.840.1.113883
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.16.840.1.113883', 'hl7', '0', '1');

-- 2.16.840.1.113883.3
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.16.840.1.113883.3', 'externalUseRoots', '0', '1');

-- 2.16.840.1.113883.6
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.16.840.1.113883.6', 'externalCodeSystems', '0', '1');

-- 2.16.840.1.113883.13
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.16.840.1.113883.13', 'externalValueSets', '0', '1');

-- 2.23
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.23', 'international-organizations', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.23', 'International-Organizations', '1', '1');

-- 2.25
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.25', 'uuid', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.25', 'UUID', '1', '1');

-- 2.27
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.27', 'tag-based', '0', '1');
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.27', 'nid', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.27', 'Tag-Based', '1', '1');

-- 2.40
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.40', 'upu', '0', '1');

-- 2.40.2
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.40.2', 'member-body', '0', '1');

-- 2.40.3
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.40.3', 'identified-organization', '0', '1');

-- 2.41
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.41', 'bip', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.41', 'BIP', '1', '1');

-- 2.42
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.42', 'telebiometrics', '0', '1');

-- 2.48
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.48', 'cybersecurity', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.48', 'Cybersecurity', '1', '1');

-- 2.49
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.49', 'alerting', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.49', 'Alerting', '1', '1');

-- 2.49.0
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.49.0', 'wmo', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.49.0', 'WMO', '0', '1');

-- 2.49.0.0
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.49.0.0', 'authority', '0', '1');

-- 2.49.0.1
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.49.0.1', 'country-msg', '0', '1');

-- 2.49.0.2
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.49.0.2', 'org', '0', '1');

-- 2.49.0.3
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.49.0.3', 'org-msg', '0', '1');

-- 2.50
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.50', 'ors', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.50', 'ORS', '1', '1');

-- 2.51
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.51', 'gs1', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.51', 'GS1', '1', '1');

-- 2.999
INSERT INTO "ASN1ID" (oid, name, standardized, well_known) VALUES ('oid:2.999', 'example', '0', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.999', 'Example', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.999', 'Exemple', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.999', 'Ejemplo', '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.999', base64_decode('2KfZhNmF2KvYp9mE'), '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.999', base64_decode('6IyD5L6L'), '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.999', base64_decode('0J/RgNC40LzQtdGA'), '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.999', base64_decode('7JiI7KCc'), '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.999', base64_decode('5L6L'), '1', '1');
INSERT INTO "IRI" (oid, name, longarc, well_known) VALUES ('oid:2.999', 'Beispiel', '1', '1');

-- Generator "generate_wellknown_other_firebird" checksum 89073e54
