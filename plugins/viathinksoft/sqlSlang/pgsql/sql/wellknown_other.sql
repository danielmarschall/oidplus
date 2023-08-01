-- This file (wellknown_other_pgsql.sql) contains ASN.1 and IRI names of OIDs which are either
-- a) Root OIDs
-- b) Unicode labels which are long arcs
-- c) Standardized ASN.1 identifiers
-- d) OIDs where potential users of this software can register OIDs in these arcs (e.g. an "identified organization" arc)
-- Use the tool dev/generate_wellknown_other_pgsql to generate this file

-- 0
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0', 'itu-t', true, true);
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0', 'ccitt', true, true);
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0', 'itu-r', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0', 'ITU-T', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0', 'ITU-R', false, true);

-- 0.0
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0', 'recommendation', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0', 'Recommendation', false, true);

-- 0.0.1
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.1', 'a', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.1', 'A', false, true);

-- 0.0.2
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.2', 'b', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.2', 'B', false, true);

-- 0.0.3
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.3', 'c', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.3', 'C', false, true);

-- 0.0.4
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.4', 'd', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.4', 'D', false, true);

-- 0.0.5
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.5', 'e', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.5', 'E', false, true);

-- 0.0.6
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.6', 'f', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.6', 'F', false, true);

-- 0.0.7
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.7', 'g', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.7', 'G', false, true);

-- 0.0.8
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.8', 'h', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.8', 'H', false, true);

-- 0.0.9
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.9', 'i', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.9', 'I', false, true);

-- 0.0.10
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.10', 'j', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.10', 'J', false, true);

-- 0.0.11
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.11', 'k', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.11', 'K', false, true);

-- 0.0.12
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.12', 'l', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.12', 'L', false, true);

-- 0.0.13
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.13', 'm', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.13', 'M', false, true);

-- 0.0.14
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.14', 'n', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.14', 'N', false, true);

-- 0.0.15
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.15', 'o', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.15', 'O', false, true);

-- 0.0.16
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.16', 'p', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.16', 'P', false, true);

-- 0.0.17
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.17', 'q', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.17', 'Q', false, true);

-- 0.0.18
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.18', 'r', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.18', 'R', false, true);

-- 0.0.19
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.19', 's', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.19', 'S', false, true);

-- 0.0.20
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.20', 't', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.20', 'T', false, true);

-- 0.0.21
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.21', 'u', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.21', 'U', false, true);

-- 0.0.22
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.22', 'v', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.22', 'V', false, true);

-- 0.0.23
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.23', 'w', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.23', 'W', false, true);

-- 0.0.24
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.24', 'x', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.24', 'X', false, true);

-- 0.0.25
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.25', 'y', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.25', 'Y', false, true);

-- 0.0.26
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.0.26', 'z', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.0.26', 'Z', false, true);

-- 0.1
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.1', 'question', true, true);

-- 0.2
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.2', 'administration', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.2', 'Administration', false, true);

-- 0.3
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.3', 'network-operator', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.3', 'Network-Operator', false, true);

-- 0.4
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.4', 'identified-organization', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:0.4', 'Identified-Organization', false, true);

-- 0.4.0
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.4.0', 'etsi', false, true);

-- 0.4.0.127
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.4.0.127', 'reserved', false, true);

-- 0.4.0.127.0
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:0.4.0.127.0', 'etsi-identified-organization', false, true);

-- 1
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1', 'iso', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:1', 'ISO', false, true);

-- 1.0
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.0', 'standard', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:1.0', 'Standard', false, true);

-- 1.1
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.1', 'registration-authority', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:1.1', 'Registration-Authority', false, true);

-- 1.1.19785
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.1.19785', 'cbeff', false, true);

-- 1.1.19785.0
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.1.19785.0', 'biometric-organization', false, true);
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.1.19785.0', 'organization', false, true);
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.1.19785.0', 'organizations', false, true);

-- 1.2
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.2', 'member-body', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:1.2', 'Member-Body', false, true);

-- 1.2.276.0
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.2.276.0', 'din-certco', false, true);

-- 1.2.616.1
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.2.616.1', 'organization', false, true);

-- 1.2.826.0
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.2.826.0', 'national', false, true);

-- 1.2.826.0.1
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.2.826.0.1', 'eng-ltd', false, true);

-- 1.2.840.1
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.2.840.1', 'organization', false, true);

-- 1.2.840.113556
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.2.840.113556', 'microsoft', false, true);

-- 1.2.840.113556.2
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.2.840.113556.2', 'dicom', false, true);

-- 1.3
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.3', 'identified-organization', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:1.3', 'Identified-Organization', false, true);

-- 1.3.6
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.3.6', 'dod', false, true);

-- 1.3.6.1
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1', 'internet', false, true);

-- 1.3.6.1.2
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.2', 'mgmt', false, true);

-- 1.3.6.1.2.1
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.2.1', 'mib-2', false, true);

-- 1.3.6.1.4
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4', 'private', false, true);

-- 1.3.6.1.4.1
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1', 'enterprise', false, true);

-- 1.3.6.1.4.1.12798.1
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.12798.1', 'member', false, true);

-- 1.3.6.1.4.1.37476.9000
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.37476.9000', 'freeoid', false, true);
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.37476.9000', 'freesub', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:1.3.6.1.4.1.37476.9000', 'FreeOID', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:1.3.6.1.4.1.37476.9000', 'FreeSub', false, true);

-- 1.3.6.1.4.1.37553.8
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.37553.8', 'weid', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:1.3.6.1.4.1.37553.8', 'weid', false, true);

-- 1.3.6.1.4.1.37553.8.8
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.37553.8.8', 'private-weid', false, true);

-- 1.3.6.1.4.1.37553.8.9
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.37553.8.9', 'ns', false, true);

-- 1.3.6.1.4.1.37553.8.9.17704
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.37553.8.9.17704', 'dns', false, true);

-- 1.3.6.1.4.1.37553.8.9.1439221
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:1.3.6.1.4.1.37553.8.9.1439221', 'uuid', false, true);

-- 2
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2', 'joint-iso-itu-t', true, true);
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2', 'joint-iso-ccitt', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2', 'Joint-ISO-ITU-T', false, true);

-- 2.1
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.1', 'asn1', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.1', 'ASN.1', true, true);

-- 2.16
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.16', 'country', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.16', 'Country', true, true);

-- 2.16.158.1
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.16.158.1', 'organization', false, true);

-- 2.16.344.1
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.16.344.1', 'organization', false, true);

-- 2.16.840.1
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.16.840.1', 'organization', false, true);

-- 2.16.840.1.113883
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.16.840.1.113883', 'hl7', false, true);

-- 2.16.840.1.113883.3
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.16.840.1.113883.3', 'externalUseRoots', false, true);

-- 2.16.840.1.113883.6
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.16.840.1.113883.6', 'externalCodeSystems', false, true);

-- 2.16.840.1.113883.13
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.16.840.1.113883.13', 'externalValueSets', false, true);

-- 2.23
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.23', 'international-organizations', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.23', 'International-Organizations', true, true);

-- 2.25
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.25', 'uuid', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.25', 'UUID', true, true);

-- 2.27
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.27', 'tag-based', false, true);
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.27', 'nid', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.27', 'Tag-Based', true, true);

-- 2.40
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.40', 'upu', false, true);

-- 2.40.2
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.40.2', 'member-body', false, true);

-- 2.40.3
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.40.3', 'identified-organization', false, true);

-- 2.41
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.41', 'bip', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.41', 'BIP', true, true);

-- 2.42
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.42', 'telebiometrics', false, true);

-- 2.48
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.48', 'cybersecurity', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.48', 'Cybersecurity', true, true);

-- 2.49
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.49', 'alerting', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.49', 'Alerting', true, true);

-- 2.49.0
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.49.0', 'wmo', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.49.0', 'WMO', false, true);

-- 2.49.0.0
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.49.0.0', 'authority', false, true);

-- 2.49.0.1
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.49.0.1', 'country-msg', false, true);

-- 2.49.0.2
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.49.0.2', 'org', false, true);

-- 2.49.0.3
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.49.0.3', 'org-msg', false, true);

-- 2.50
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.50', 'ors', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.50', 'ORS', true, true);

-- 2.51
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.51', 'gs1', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.51', 'GS1', true, true);

-- 2.999
INSERT INTO "asn1id" (oid, name, standardized, well_known) VALUES ('oid:2.999', 'example', false, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.999', 'Example', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.999', 'Exemple', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.999', 'Ejemplo', true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.999', convert_from(decode('2KfZhNmF2KvYp9mE', 'base64'), 'utf-8'), true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.999', convert_from(decode('6IyD5L6L', 'base64'), 'utf-8'), true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.999', convert_from(decode('0J/RgNC40LzQtdGA', 'base64'), 'utf-8'), true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.999', convert_from(decode('7JiI7KCc', 'base64'), 'utf-8'), true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.999', convert_from(decode('5L6L', 'base64'), 'utf-8'), true, true);
INSERT INTO "iri" (oid, name, longarc, well_known) VALUES ('oid:2.999', 'Beispiel', true, true);

-- Generator "generate_wellknown_other_pgsql" checksum 55f8f00d
