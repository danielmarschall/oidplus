// **************************************************
// ** OID CSV Lookup Server 1.3                    **
// ** (c) 2016-2024 ViaThinkSoft, Daniel Marschall **
// **************************************************

// todo: log verbosity + datetime
// todo: publish at codelib
// todo: server hat sich einfach so beendet... "read: connection reset by peer"
// todo: 2019-02-24 service was booted together with the system, and i got "0 OIDs loaded". why???
// todo: create vnag monitor that checks if this service is OK
// todo: unexplained crash with version 1.1 : Signal "pipefail" (141) received after someone connected?!

#include "oid_lookup_srv.h"

unordered_set<string> lines;

time_t csvLoad = time(NULL);

int loadfile(const string &filename) {
	int cnt = 0;

	ifstream infile(filename);
	string line;
	while (getline(infile, line)) {
		lines.insert(line);
		++cnt;
	}
	infile.close();

	fprintf(stdout, "Loaded %d OIDs from %s\n", cnt, filename.c_str());

	return cnt;
}

int loadCSVs() {
	return loadfile("oid_table.csv");
}

bool stringAvailable(const string &str) {
	return lines.find(str) != lines.end();
}

// Template of this code: http://www.gnu.org/software/libc/manual/html_node/Server-Example.html
//                        http://www.gnu.org/software/libc/manual/html_node/Inet-Example.html#Inet-Example

struct con_descriptor {
	time_t              last_activity;
	struct sockaddr_in  clientname;
	int                 queries;
	time_t              connect_ts;
};

con_descriptor cons[FD_SETSIZE];

int read_from_client(int filedes) {
	char buffer[MAXMSG];
	int nbytes;

	nbytes = read(filedes, buffer, sizeof(buffer));
	buffer[sizeof(buffer)-1] = 0; // Terminator

	if (nbytes < 0) {
		/* Read error. */
		//perror("read");
		//exit(EXIT_FAILURE);
		return -1;
	} else if (nbytes == 0) {
		/* End-of-file. */
		return -1;
	} else {
		/* Data read. */

		for (uint i=0; i<sizeof(buffer); ++i) {
			if ((buffer[i] == 13) || (buffer[i] == 10)) {
				buffer[i] = 0; // Terminator
				break;
			}
		}

		if (strcmp(buffer, "bye") == 0) {
			fprintf(stdout, "%s:%d[%d] Client said good bye.\n", inet_ntoa(cons[filedes].clientname.sin_addr), ntohs(cons[filedes].clientname.sin_port), filedes);
			return -1;
		} else if (strcmp(buffer, "reload") == 0) {
			fprintf(stdout, "%s:%d[%d] Client requested a reload.\n", inet_ntoa(cons[filedes].clientname.sin_addr), ntohs(cons[filedes].clientname.sin_port), filedes);
			loadCSVs();
			csvLoad = time(NULL);
			write(filedes, "OK\n", 3);
			return 0;
		} else {
			cons[filedes].queries++;

			for (uint i=0; i<sizeof(buffer); ++i) {
				if (buffer[i] == 0) break;
				if (!((buffer[i] >= '0') && (buffer[i] <= '9')) && !(buffer[i] == '.')) {
					fprintf(stdout, "%s:%d[%d] Client sent an invalid request.\n", inet_ntoa(cons[filedes].clientname.sin_addr), ntohs(cons[filedes].clientname.sin_port), filedes);
					return -1;
				}
			}

			// fprintf(stdout, "%s:%d[%d] Query #%d: %s\n", inet_ntoa(cons[filedes].clientname.sin_addr), ntohs(cons[filedes].clientname.sin_port), filedes, cons[filedes].queries, buffer);

			if (stringAvailable(buffer)) {
				write(filedes, "1\n", 2);
			} else {
				write(filedes, "0\n", 2);
			}
			return 0;
		}
	}
}

int fd_set_isset_count(const fd_set &my_fd_set) {
	int cnt = 0;
	for (int fd = 0; fd < FD_SETSIZE; ++fd) {
		if (FD_ISSET(fd, &my_fd_set)) {
			++cnt;
		}
	}
	return cnt;
}

void initConsArray() {
	for (int i=0; i<FD_SETSIZE; ++i) {
		cons[i].last_activity = 0;
		memset(&cons[i].clientname, 0, sizeof(sockaddr_in));
		cons[i].queries = 0;
	}
}

int make_socket(uint16_t port) {
	int sock;
	struct sockaddr_in name;

	/* Create the socket. */
	sock = socket(PF_INET, SOCK_STREAM, 0);
	if (sock < 0) {
		perror("socket");
		exit(EXIT_FAILURE);
	}

	int enable = 1;
	if (setsockopt(sock, SOL_SOCKET, SO_REUSEADDR, &enable, sizeof(int)) < 0) {
		fprintf(stderr, "ERROR: setsockopt(SO_REUSEADDR) failed");
		exit(EXIT_FAILURE);
	}

	/* Give the socket a name. */
	name.sin_family = AF_INET;
	name.sin_port = htons (port);
	name.sin_addr.s_addr = htonl (INADDR_ANY);
	if (bind (sock, (struct sockaddr *) &name, sizeof(name)) < 0) {
		perror("bind");
		exit(EXIT_FAILURE);
	}

	return sock;
}

int main(void) {
//	extern int make_socket(uint16_t port);
	int sock;
	fd_set active_fd_set, read_fd_set;

	fprintf(stdout, "OID CSV Lookup Server 1.3 (c)2016-2024 ViaThinkSoft\n");
	fprintf(stdout, "Listening at port: %d\n", PORT);
	fprintf(stdout, "Max connections: %d\n", FD_SETSIZE);

	initConsArray();

	int loadedOIDs = loadCSVs();

	/* Create the socket and set it up to accept connections. */
	sock = make_socket(PORT);
	if (listen(sock, 1) < 0) {
		perror("listen");
		exit(EXIT_FAILURE);
	}

	/* Initialize the set of active sockets. */
	FD_ZERO(&active_fd_set);
	FD_SET(sock, &active_fd_set);

	while (1) {
		/* Block until input arrives on one or more active sockets. */
		read_fd_set = active_fd_set;

		struct timeval tv;
		tv.tv_sec = 1;
		tv.tv_usec = 0;  // Not init'ing this can cause strange errors

		int retval = select(FD_SETSIZE, &read_fd_set, NULL, NULL, &tv);

		if (retval < 0) {
			perror("select");
			exit(EXIT_FAILURE);
		} else if (retval == 0) {
			// fprintf(stdout, "Nothing received\n");
		} else {
			/* Service all the sockets with input pending. */
			for (int i=0; i < FD_SETSIZE; ++i) {
				if (FD_ISSET (i, &read_fd_set)) {
					if (i == sock) {
						/* Connection request on original socket. */
						int new_fd;
						struct sockaddr_in clientname;
						socklen_t size = sizeof(clientname);
						new_fd = accept(sock, (struct sockaddr *) &clientname, &size);
						if (new_fd < 0) {
							perror("accept");
							exit(EXIT_FAILURE);
						}
						FD_SET(new_fd, &active_fd_set);

						cons[new_fd].clientname = clientname;
						cons[new_fd].connect_ts = time(NULL);

						if (loadedOIDs == 0) {
							loadedOIDs = loadCSVs();
							if (loadedOIDs == 0) {
								fprintf(stderr, "%s:%d[%d] Service temporarily unavailable (OID list empty)\n", inet_ntoa(clientname.sin_addr), ntohs(clientname.sin_port), new_fd);
								close(new_fd);
								FD_CLR(new_fd, &active_fd_set);
							}
						}

						if (fd_set_isset_count(active_fd_set)-1 > MAX_CONNECTIONS) { // -1 is because we need to exclude the listening socket (i=sock) which is not a connected client
							fprintf(stderr, "%s:%d[%d] Rejected because too many connections are open\n", inet_ntoa(clientname.sin_addr), ntohs(clientname.sin_port), new_fd);
							close(new_fd);
							FD_CLR(new_fd, &active_fd_set);
						} else {
							fprintf(stdout, "%s:%d[%d] Connected\n", inet_ntoa(clientname.sin_addr), ntohs(clientname.sin_port), new_fd);
						}

						if (new_fd >= FD_SETSIZE) {
							fprintf(stderr, "%s:%d[%d] new_fd reached cons[FD_SETSIZE] limit\n", inet_ntoa(clientname.sin_addr), ntohs(clientname.sin_port), new_fd);
							close(new_fd);
							FD_CLR(new_fd, &active_fd_set);
						}

						cons[new_fd].last_activity = time(NULL);
						cons[new_fd].queries = 0;
					} else {
						/* Data arriving on an already-connected socket. */
						cons[i].last_activity = time(NULL);
						if (read_from_client(i) < 0) {
							fprintf(stdout, "%s:%d[%d] Connection closed after %d queries in %lu seconds.\n", inet_ntoa(cons[i].clientname.sin_addr), ntohs(cons[i].clientname.sin_port), i, cons[i].queries, time(NULL)-cons[i].connect_ts);
							close(i);
							FD_CLR(i, &active_fd_set);
						}
					}
				}
			}
		}

		/* Check if we need to reload the CSV */
		if (time(NULL)-csvLoad >= CSVRELOADINTERVAL) {
			loadCSVs();
			csvLoad = time(NULL);
		}

		/* Check if we can close connections due to timeout */
		for (int i=0; i < FD_SETSIZE; ++i) {
			if (FD_ISSET(i, &active_fd_set)) {
				if (i == sock) continue;
				if (time(NULL)-cons[i].last_activity >= CONNECTION_TIMEOUT) {
					fprintf(stdout, "%s:%d[%d] Connection timeout.\n", inet_ntoa(cons[i].clientname.sin_addr), ntohs(cons[i].clientname.sin_port), i);
					fprintf(stdout, "%s:%d[%d] Connection closed after %d queries in %lu seconds.\n", inet_ntoa(cons[i].clientname.sin_addr), ntohs(cons[i].clientname.sin_port), i, cons[i].queries, time(NULL)-cons[i].connect_ts);
					close(i);
					FD_CLR(i, &active_fd_set);
				}
			}
		}
	}
}
