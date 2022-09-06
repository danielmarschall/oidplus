/*###################################################################
###                                                               ###
### Object ID converter. Matthias Gaertner, 06/1999               ###
### Converted to plain 'C' 07/2001                                ###
###                                                               ###
### To compile with gcc simply use:                               ###
###   gcc -O2 -o oid oid.c                                        ###
###                                                               ###
### To compile using cl, use:                                     ###
###   cl -DWIN32 -O1 oid.c                                        ###
###                                                               ###
### Freeware - do with it whatever you want.                      ###
### Use at your own risk. No warranty of any kind.                ###
###                                                               ###
###################################################################*/
/* $Version: 1.1$ */

#include <stdio.h>
#include <string.h>
#include <stdlib.h>

#ifndef __STRNICMP_LOCAL
#ifdef WIN32
#define __STRNICMP_LOCAL strnicmp
#else
#define __STRNICMP_LOCAL strncasecmp
#endif
#endif

char			abCommandLine[256];
unsigned char	abBinary[128];
unsigned int	nBinary = 0;


static void MakeBase128( unsigned long l, int first )
{
	if( l > 127 )
	{
		MakeBase128( l / 128, 0 );
	}
	l %= 128;
	if( first )
	{
		abBinary[nBinary++] = (unsigned char)l;
	}
	else
	{
		abBinary[nBinary++] = 0x80 | (unsigned char)l;
	}
}

int main( int argc, char **argv )
{
	char *fOutName = NULL;
	char *fInName = NULL;
	FILE *fOut = NULL;

	int n = 1;
	int nMode = 0;	/* dotted->hex */
	int nCHex = 0;
	int nAfterOption = 0;

	if( argc == 1 )
	{
		fprintf( stderr,
		"OID encoder/decoder - Matthias Gaertner 1999/2001 - Freeware\n"
		"Usage:\n"
		" OID [-C] [-o<outfile>] {-i<infile>|1.2.3.4}\n"
		"   converts dotted form to ASCII HEX DER output.\n"
		" OID -x [-o<outfile>] {-i<infile>|hex-digits}\n"
		"   decodes ASCII HEX DER and gives dotted form.\n" );
		return 1;
	}

	while( n < argc )
	{
		if( !nAfterOption && argv[n][0] == '-' )
		{
			if( argv[n][1] == 'x' )
			{
				nMode = 1;	/* hex->dotted */
				if( argv[n][2] != '\0' )
				{
					argv[n--] += 2;
					nAfterOption = 1;
				}
			}
			else if( argv[n][1] == 'C' )
			{
				nMode = 0;
				nCHex = 1;

				if( argv[n][2] != '\0' )
				{
					argv[n--] += 2;
					nAfterOption = 1;
				}
			}
			else if( argv[n][1] == 'o' )
			{
				if( argv[n][2] != '\0' )
				{
					fOutName = &argv[n][2];
				}
				else if( n < argc-1 )
				{
					fOutName = argv[++n];
				}
				else
				{
					fprintf(stderr,"Incomplete command line.\n");
				}
			}
			else if( argv[n][1] == 'i' )
			{
				if( argv[n][2] != '\0' )
				{
					fInName = &argv[n][2];
				}
				else if( n < argc-1 )
				{
					fInName = argv[++n];
				}
				else
				{
					fprintf(stderr,"Incomplete command line.\n");
				}
			}
		}
		else
		{
			if( fInName != NULL )
			{
				break;
			}

			nAfterOption = 1;
			if( strlen( argv[n] ) + strlen( abCommandLine ) >= sizeof(abCommandLine)-2 )
			{
				fprintf(stderr,"Command line too long.\n");
				return 2;
			}
			strcat( abCommandLine, argv[n] );
			if( n != argc - 1 && nMode != 1 )
			{
				strcat( abCommandLine, "." );
			}
		}
		n++;
	}

	if( fInName != NULL && nMode == 1 )
	{
		FILE *fIn = fopen( fInName, "rb" );
		size_t nRead = 0;
		if( fIn == NULL )
		{
			fprintf(stderr,"Unable to open input file %s.\n", fInName );
			return 11;
		}
		nRead = fread( abCommandLine, 1, sizeof(abCommandLine), fIn );
		abCommandLine[nRead] = '\0';
		fclose( fIn );
	}
	else if( fInName != NULL && nMode == 0 )
	{
		FILE *fIn = fopen( fInName, "rt" );
		if( fIn == NULL )
		{
			fprintf(stderr,"Unable to open input file %s.\n", fInName );
			return 11;
		}
		fgets( abCommandLine, sizeof(abCommandLine), fIn );
		fclose( fIn );
	}

	while( nMode == 1 )	/* better if */
	{
		/* hex->dotted */
		/*printf("Hex-In: %s\n", abCommandLine );*/

		char *p = abCommandLine;
		char *q = p;

		unsigned char *pb = NULL;
		unsigned int nn = 0;
		unsigned long ll = 0;
		int fOK = 0;

		while( *p )
		{
			if( *p != '.' && *p != '\r' && *p != '\n' && *p != '\x20' && *p != '\t')
			{
				*q++ = *p;
			}
			p++;
		}
		*q = '\0';

		if( strlen( abCommandLine ) % 2 != 0 )
		{
			fprintf(stderr, "OID must have even number of hex digits!\n" );
			return 2;
		}

		if( strlen( abCommandLine ) < 3 )
		{
			fprintf(stderr, "OID must have at least three bytes!\n" );
			return 2;
		}

		nBinary = 0;
		p = abCommandLine;

		while( *p )
		{
			unsigned char b;
			if( p[0] >= 'A' && p[0] <= 'F' )
			{
				b = (p[0] - 'A' + 10) * 16;
			}
			else if( p[0] >= 'a' && p[0] <= 'f' )
			{
				b = (p[0] - 'a' + 10) * 16;
			}
			else if( p[0] >= '0' && p[0] <= '9' )
			{
				b = (p[0] - '0') * 16;
			}
			else
			{
				fprintf(stderr, "Must have hex digits only!\n" );
				return 2;
			}
			if( p[1] >= 'A' && p[1] <= 'F' ||
				p[1] >= 'a' && p[1] <= 'f' )
			{
				b += (p[1] - 'A' + 10);
			}
			else if( p[1] >= '0' && p[1] <= '9' )
			{
				b += (p[1] - '0');
			}
			else
			{
				fprintf(stderr, "Must have hex digits only!\n" );
				return 2;
			}
			abBinary[nBinary++] = b;
			p += 2;
		}

		/*printf("Hex-In: %s\n", abCommandLine );*/

		if( fOutName != NULL )
		{
			fOut = fopen( fOutName, "wt" );
			if( fOut == NULL )
			{
				fprintf(stderr,"Unable to open output file %s\n", fOutName );
				return 33;
			}
		}
		else
		{
			fOut = stdout;
		}

		pb = abBinary;
		nn = 0;
		ll = 0;
		fOK = 0;
		while( nn < nBinary )
		{
			if( nn == 0 )
			{
				unsigned char cl = ((*pb & 0xC0) >> 6) & 0x03;
				switch( cl )
				{
				default:
				case 0: fprintf(fOut,"UNIVERSAL"); break;
				case 1: fprintf(fOut,"APPLICATION"); break;
				case 2: fprintf(fOut,"CONTEXT"); break;
				case 3: fprintf(fOut,"PRIVATE"); break;
				}
				fprintf(fOut," OID");
			}
			else if( nn == 1 )
			{
				if( nBinary - 2 != *pb )
				{
					if( fOut != stdout )
					{
						fclose( fOut );
					}
					fprintf(stderr,"\nInvalid length (%d)\n", *pb );
					return 3;
				}
			}
			else if( nn == 2 )
			{
				fprintf(fOut,".%d.%d", *pb / 40, *pb % 40 );
				fOK = 1;
				ll = 0;
			}
			else if( (*pb & 0x80) != 0 )
			{
				ll *= 128;
				ll += (*pb & 0x7F);
				fOK = 0;
			}
			else
			{
				ll *= 128;
				ll += *pb;
				fOK = 1;

				fprintf(fOut,".%lu", ll );
				ll = 0;
			}

			pb++;
			nn++;
		}

		if( !fOK )
		{
			fprintf(stderr,"\nEncoding error. The OID is not constructed properly.\n");
			return 4;
		}
		else
		{
			fprintf(fOut,"\n");
		}

		if( fOut != stdout )
		{
			fclose( fOut );
		}
		break;
	};

	while( nMode == 0 )	/* better if */
	{
		/* dotted->hex */
		/* printf("OID.%s\n", abCommandLine ); */

		char *p = abCommandLine;
		unsigned char cl = 0x00;
		char *q = NULL;
		int nPieces = 1;
		int n = 0;
		unsigned char b = 0;
		unsigned int nn = 0;
		unsigned long l = 0;

		if( __STRNICMP_LOCAL( p, "UNIVERSAL.", 10 ) == 0 )
		{
			p+=10;
		}
		else if( __STRNICMP_LOCAL( p, "APPLICATION.", 12 ) == 0 )
		{
			cl = 0x40;
			p+=12;
		}
		else if( __STRNICMP_LOCAL( p, "CONTEXT.", 8 ) == 0 )
		{
			cl = 0x80;
			p+=8;
		}
		else if( __STRNICMP_LOCAL( p, "PRIVATE.", 8 ) == 0 )
		{
			cl = 0xC0;
			p+=8;
		}
		if( __STRNICMP_LOCAL( p, "OID.", 4 ) == 0 )
		{
			p+=4;
		}

		q = p;
		nPieces = 1;
		while( *p )
		{
			if( *p == '.' )
			{
				nPieces++;
			}
			p++;
		}

		n = 0;
		b = 0;
		p = q;
		while( n < nPieces )
		{
			q = p;
			while( *p )
			{
				if( *p == '.' )
				{
					break;
				}
				p++;
			}

			l = 0;
			if( *p == '.' )
			{
				*p = 0;
				l = (unsigned long) atoi( q );
				q = p+1;
				p = q;
			}
			else
			{
				l = (unsigned long) atoi( q );
				q = p;
			}

			/* Digit is in l. */
			if( n == 0 )
			{
				b = 40 * ((unsigned char)l);
			}
			else if( n == 1 )
			{
				b += ((unsigned char) l);
				abBinary[nBinary++] = b;
			}
			else
			{
				MakeBase128( l, 1 );
			}
			n++;
		}

		if( fOutName != NULL )
		{
			fOut = fopen( fOutName, "wt" );
			if( fOut == NULL )
			{
				fprintf(stderr,"Unable to open output file %s\n", fOutName );
				return 33;
			}
		}
		else
		{
			fOut = stdout;
		}

		if( nCHex )
		{
			fprintf(fOut,"\"\\x%02X\\x%02X", cl | 6, nBinary );
		}
		else
		{
			fprintf(fOut,"%02X %02X ", cl | 6, nBinary );
		}
		nn = 0;
		while( nn < nBinary )
		{
			unsigned char b = abBinary[nn++];
			if( nn == nBinary )
			{
				if( nCHex )
				{
					fprintf(fOut,"\\x%02X\"\n", b );
				}
				else
				{
					fprintf(fOut,"%02X\n", b );
				}
			}
			else
			{
				if( nCHex )
				{
					fprintf(fOut,"\\x%02X", b );
				}
				else
				{
					fprintf(fOut,"%02X ", b );
				}
			}
		}
		if( fOut != stdout )
		{
			fclose( fOut );
		}
		break;
	}

	return 0;
}

/* */

