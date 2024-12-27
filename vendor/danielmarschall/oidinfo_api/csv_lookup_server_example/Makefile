
all: oid_lookup_srv

oid_lookup_srv: oid_lookup_srv.cpp
	g++ -std=c++11 -lgmpxx -Wall -fopenmp -O3 -mtune=native -pipe -O3 -mtune=native -o oid_lookup_srv oid_lookup_srv.cpp

clean:
	rm -f  *.o
	# TODO: if [ -f ... ] then rm
	rm oid_lookup_srv
